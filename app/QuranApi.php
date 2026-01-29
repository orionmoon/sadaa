<?php
/**
 * Sadaa (صدى) - Quran API Client
 * 
 * Client for the AlQuran.cloud API
 * Documentation: https://alquran.cloud/api
 */

class QuranApi
{
    private string $baseUrl = 'https://api.alquran.cloud/v1';
    private int $timeout = 30;

    /**
     * Available Quran editions for translations
     */
    public static array $editions = [
        'ar' => 'quran-uthmani',           // Arabic Uthmani script
        'fr' => 'fr.hamidullah',            // French - Muhammad Hamidullah
        'en' => 'en.sahih',                 // English - Saheeh International
        'es' => 'es.cortes',                // Spanish - Julio Cortes
        'de' => 'de.aburida',               // German - Abu Rida
        'tr' => 'tr.diyanet',               // Turkish - Diyanet
        'id' => 'id.indonesian',            // Indonesian
        'ur' => 'ur.jalandhry',             // Urdu
        'bn' => 'bn.bengali',               // Bengali
        'ru' => 'ru.kuliev',                // Russian - Elmir Kuliev
        'pt' => 'pt.elhayek',               // Portuguese
        'nl' => 'nl.keyzer',                // Dutch
        'it' => 'it.piccardo',              // Italian
        'fa' => 'fa.makarem',               // Persian/Farsi
        'zh' => 'zh.majian',                // Chinese
        'ja' => 'ja.japanese',              // Japanese
        'ko' => 'ko.korean',                // Korean
    ];

    /**
     * Make an API request
     */
    private function request(string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);


        if ($error) {
            throw new Exception("API request failed: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("API returned HTTP $httpCode");
        }

        $data = json_decode($response, true);

        if (!$data || $data['code'] !== 200) {
            throw new Exception("API error: " . ($data['status'] ?? 'Unknown error'));
        }

        return $data['data'];
    }

    /**
     * Get all available editions
     */
    public function getEditions(): array
    {
        return $this->request('/edition');
    }

    /**
     * Get metadata for a specific edition
     */
    public function getEditionMetadata(string $editionIdentifier): ?array
    {
        try {
            $allEditions = $this->getEditions();
            foreach ($allEditions as $edition) {
                if ($edition['identifier'] === $editionIdentifier) {
                    return $edition;
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("Failed to get edition metadata: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build translation references metadata for import
     *
     * @param array $languages Array of language codes
     * @param PDO|null $pdo Optional database connection to check for custom editions
     */
    public function buildTranslationReferences(array $languages, ?PDO $pdo = null): array
    {
        $references = [];
        foreach ($languages as $lang) {
            // First check database for custom edition, then fallback to static list
            $edition = null;
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT quran_edition FROM languages WHERE code = ? AND quran_edition IS NOT NULL AND quran_edition != ''");
                $stmt->execute([$lang]);
                $edition = $stmt->fetchColumn();
            }
            if (!$edition) {
                $edition = self::getEditionForLanguage($lang);
            }

            if ($edition) {
                $metadata = $this->getEditionMetadata($edition);
                $references[$lang] = [
                    'identifier' => $edition,
                    'name' => $metadata['name'] ?? $edition,
                    'language' => $metadata['language'] ?? $lang,
                    'englishName' => $metadata['englishName'] ?? '',
                    'format' => $metadata['format'] ?? 'text',
                    'type' => $metadata['type'] ?? 'translation'
                ];
            }
        }
        return $references;
    }

    /**
     * Get surah list
     */
    public function getSurahList(): array
    {
        return $this->request('/surah');
    }

    /**
     * Get a single surah with translation
     * 
     * @param int $surahNumber 1-114
     * @param string $edition Edition identifier (e.g., 'quran-uthmani', 'fr.hamidullah')
     */
    public function getSurah(int $surahNumber, string $edition = 'quran-uthmani'): array
    {
        if ($surahNumber < 1 || $surahNumber > 114) {
            throw new InvalidArgumentException("Invalid surah number: $surahNumber");
        }

        return $this->request("/surah/$surahNumber/$edition");
    }

    /**
     * Get a specific ayah
     * 
     * @param int $surahNumber 1-114
     * @param int $ayahNumber Ayah number in surah
     * @param string $edition Edition identifier
     */
    public function getAyah(int $surahNumber, int $ayahNumber, string $edition = 'quran-uthmani'): array
    {
        $reference = "$surahNumber:$ayahNumber";
        return $this->request("/ayah/$reference/$edition");
    }

    /**
     * Get multiple editions of the same surah
     * Useful for getting Arabic + translation in one call
     * 
     * @param int $surahNumber 1-114
     * @param array $editions Array of edition identifiers
     */
    public function getSurahWithEditions(int $surahNumber, array $editions): array
    {
        $editionString = implode(',', $editions);
        return $this->request("/surah/$surahNumber/editions/$editionString");
    }

    /**
     * Get edition code for a language
     */
    public static function getEditionForLanguage(string $langCode): ?string
    {
        return self::$editions[$langCode] ?? null;
    }

    /**
     * Import a complete surah into the database
     * 
     * @param PDO $pdo Database connection
     * @param int $surahNumber 1-114
     * @param array $languages Language codes to import ['ar', 'fr', 'en']
     * @param int $bookId Book ID in database
     * @param bool $overwrite Whether to overwrite existing data
     */
    public function importSurah(PDO $pdo, int $surahNumber, array $languages, int $bookId, bool $overwrite = false): array
    {
        // Check if surah exists and overwrite is disabled
        if (!$overwrite) {
            $stmt = $pdo->prepare("SELECT id FROM surahs WHERE book_id = ? AND number = ?");
            $stmt->execute([$bookId, $surahNumber]);
            if ($stmt->fetchColumn()) {
                throw new Exception("Surah $surahNumber already exists. Enable overwrite to update.");
            }
        }

        // Always include Arabic
        if (!in_array('ar', $languages)) {
            array_unshift($languages, 'ar');
        }

        // Get Arabic edition from database or use default
        $arabicEdition = 'quran-uthmani';
        $stmt = $pdo->prepare("SELECT quran_edition FROM languages WHERE code = 'ar' AND quran_edition IS NOT NULL AND quran_edition != ''");
        $stmt->execute();
        $dbArabicEdition = $stmt->fetchColumn();
        if ($dbArabicEdition) {
            $arabicEdition = $dbArabicEdition;
        }

        // Get surah info from Arabic version
        $arabicData = $this->getSurah($surahNumber, $arabicEdition);

        // Prepare surah name in all languages
        $surahName = ['ar' => $arabicData['englishName']]; // API returns transliteration
        $surahNameArabic = $arabicData['name']; // Arabic name
        $surahName['ar'] = $surahNameArabic;

        // Insert or update surah
        $stmt = $pdo->prepare("
            INSERT INTO surahs (book_id, number, name, revelation_type, ayah_count)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                revelation_type = VALUES(revelation_type),
                ayah_count = VALUES(ayah_count)
        ");

        $revelationType = strtolower($arabicData['revelationType']) === 'meccan' ? 'meccan' : 'medinan';
        $stmt->execute([
            $bookId,
            $surahNumber,
            json_encode(['ar' => $surahNameArabic, 'en' => $arabicData['englishName']]),
            $revelationType,
            $arabicData['numberOfAyahs']
        ]);

        // Get surah ID
        $stmt = $pdo->prepare("SELECT id FROM surahs WHERE book_id = ? AND number = ?");
        $stmt->execute([$bookId, $surahNumber]);
        $surahId = $stmt->fetchColumn();

        // Collect all ayahs with translations
        $ayahsData = [];
        foreach ($arabicData['ayahs'] as $ayah) {
            $ayahsData[$ayah['numberInSurah']] = [
                'ar' => $ayah['text']
            ];
        }

        // Get translations for other languages
        foreach ($languages as $lang) {
            if ($lang === 'ar')
                continue;

            // First check database for custom edition, then fallback to static list
            $edition = null;
            $stmt = $pdo->prepare("SELECT quran_edition FROM languages WHERE code = ? AND quran_edition IS NOT NULL AND quran_edition != ''");
            $stmt->execute([$lang]);
            $dbEdition = $stmt->fetchColumn();
            $edition = $dbEdition ?: self::getEditionForLanguage($lang);

            if (!$edition)
                continue;

            try {
                $translationData = $this->getSurah($surahNumber, $edition);
                foreach ($translationData['ayahs'] as $ayah) {
                    $ayahsData[$ayah['numberInSurah']][$lang] = $ayah['text'];
                }

                // Update surah name with translation if available
                if (isset($translationData['englishName'])) {
                    $surahName[$lang] = $translationData['englishName'];
                }
            } catch (Exception $e) {
                // Log error but continue with other languages
                error_log("Failed to get $lang translation for surah $surahNumber: " . $e->getMessage());
            }
        }

        // Update surah name with all translations
        $stmt = $pdo->prepare("UPDATE surahs SET name = ? WHERE id = ?");
        $stmt->execute([json_encode($surahName), $surahId]);

        // Insert ayahs
        $insertedCount = 0;
        foreach ($ayahsData as $ayahNumber => $texts) {
            $stmt = $pdo->prepare("
                INSERT INTO ayahs (surah_id, ayah_number, text)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE text = VALUES(text)
            ");
            $stmt->execute([$surahId, $ayahNumber, json_encode($texts)]);
            $insertedCount++;
        }

        return [
            'surah_id' => $surahId,
            'surah_number' => $surahNumber,
            'surah_name' => $surahName,
            'ayahs_imported' => $insertedCount,
            'languages' => $languages
        ];
    }

    /**
     * Import the entire Quran
     * 
     * @param PDO $pdo Database connection
     * @param array $languages Language codes to import
     * @param int $bookId Book ID
     * @param callable|null $progressCallback Callback function for progress updates
     */
    public function importFullQuran(PDO $pdo, array $languages, int $bookId, ?callable $progressCallback = null): array
    {
        $totalSurahs = 114;
        $totalAyahs = 0;
        $errors = [];

        for ($i = 1; $i <= $totalSurahs; $i++) {
            try {
                $result = $this->importSurah($pdo, $i, $languages, $bookId);
                $totalAyahs += $result['ayahs_imported'];

                if ($progressCallback) {
                    $progressCallback($i, $totalSurahs, $result);
                }

                // Small delay to be nice to the API
                usleep(100000); // 100ms

            } catch (Exception $e) {
                $errors[] = [
                    'surah' => $i,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'surahs_imported' => $totalSurahs - count($errors),
            'total_surahs' => $totalSurahs,
            'ayahs_imported' => $totalAyahs,
            'languages' => $languages,
            'errors' => $errors
        ];
    }

    /**
     * Add a language to existing surahs (merge mode)
     * This preserves existing translations and ayah_categories assignments
     *
     * @param PDO $pdo Database connection
     * @param int $surahNumber 1-114
     * @param string $language Language code to add (e.g., 'es', 'de')
     * @param int $bookId Book ID in database
     * @param string|null $edition Optional edition override (from database config)
     */
    public function addLanguageToSurah(PDO $pdo, int $surahNumber, string $language, int $bookId, ?string $edition = null): array
    {
        // Check if surah exists
        $stmt = $pdo->prepare("SELECT id FROM surahs WHERE book_id = ? AND number = ?");
        $stmt->execute([$bookId, $surahNumber]);
        $surahId = $stmt->fetchColumn();

        if (!$surahId) {
            throw new Exception("Surah $surahNumber does not exist. Import it first.");
        }

        // Get edition for language (use provided edition or fallback to static list)
        if (!$edition) {
            $edition = self::getEditionForLanguage($language);
        }
        if (!$edition) {
            throw new Exception("No edition available for language: $language");
        }

        // Fetch translation from API
        $translationData = $this->getSurah($surahNumber, $edition);

        // Update surah name with new language
        $stmt = $pdo->prepare("SELECT name FROM surahs WHERE id = ?");
        $stmt->execute([$surahId]);
        $currentName = json_decode($stmt->fetchColumn(), true) ?: [];
        $currentName[$language] = $translationData['englishName'];

        $stmt = $pdo->prepare("UPDATE surahs SET name = ? WHERE id = ?");
        $stmt->execute([json_encode($currentName), $surahId]);

        // Get all existing ayahs for this surah
        $stmt = $pdo->prepare("SELECT id, ayah_number, text FROM ayahs WHERE surah_id = ?");
        $stmt->execute([$surahId]);
        $existingAyahs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build a map of ayah_number -> existing text JSON
        $ayahMap = [];
        foreach ($existingAyahs as $ayah) {
            $ayahMap[$ayah['ayah_number']] = [
                'id' => $ayah['id'],
                'text' => json_decode($ayah['text'], true) ?: []
            ];
        }

        // Merge new translation into existing text
        $updatedCount = 0;
        foreach ($translationData['ayahs'] as $ayah) {
            $ayahNumber = $ayah['numberInSurah'];

            if (isset($ayahMap[$ayahNumber])) {
                // Merge: add new language to existing JSON
                $mergedText = $ayahMap[$ayahNumber]['text'];
                $mergedText[$language] = $ayah['text'];

                $stmt = $pdo->prepare("UPDATE ayahs SET text = ? WHERE id = ?");
                $stmt->execute([json_encode($mergedText), $ayahMap[$ayahNumber]['id']]);
                $updatedCount++;
            }
        }

        return [
            'surah_id' => $surahId,
            'surah_number' => $surahNumber,
            'language_added' => $language,
            'ayahs_updated' => $updatedCount
        ];
    }

    /**
     * Add a language to multiple surahs
     *
     * @param PDO $pdo Database connection
     * @param array $surahRange [start, end] surah numbers
     * @param string $language Language code to add
     * @param int $bookId Book ID
     * @param callable|null $progressCallback Progress callback
     */
    public function addLanguageToSurahs(PDO $pdo, array $surahRange, string $language, int $bookId, ?callable $progressCallback = null): array
    {
        $start = $surahRange[0];
        $end = $surahRange[1];
        $totalUpdated = 0;
        $ayahsUpdated = 0;
        $errors = [];

        for ($i = $start; $i <= $end; $i++) {
            try {
                $result = $this->addLanguageToSurah($pdo, $i, $language, $bookId);
                $totalUpdated++;
                $ayahsUpdated += $result['ayahs_updated'];

                if ($progressCallback) {
                    $progressCallback($i, $end - $start + 1, $result);
                }

                // Small delay to be nice to the API
                usleep(100000); // 100ms

            } catch (Exception $e) {
                $errors[] = [
                    'surah' => $i,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'surahs_updated' => $totalUpdated,
            'total_surahs' => $end - $start + 1,
            'ayahs_updated' => $ayahsUpdated,
            'language' => $language,
            'errors' => $errors
        ];
    }
}
