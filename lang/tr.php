<?php
/**
 * Turkish translations
 */

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Panel',
        'books' => 'Kitaplar',
        'types' => 'Türler',
        'categories' => 'Kategoriler',
        'assignments' => 'Assignments',
        'import' => 'İçe Aktar',
        'history' => 'Geçmiş',
        'backup' => 'Yedekleme',
        'settings' => 'Ayarlar',
        'logout' => 'Çıkış',
    ],

    // Authentication
    'auth' => [
        'login' => 'Giriş',
        'password' => 'Şifre',
        'wrong_password' => 'Hatalı şifre',
        'administration' => 'Yönetim',
    ],

    // Common actions
    'actions' => [
        'save' => 'Kaydet',
        'cancel' => 'İptal',
        'delete' => 'Sil',
        'edit' => 'Düzenle',
        'add' => 'Ekle',
        'create' => 'Oluştur',
        'update' => 'Güncelle',
        'search' => 'Ara',
        'filter' => 'Filtrele',
        'export' => 'Dışa Aktar',
        'import' => 'İçe Aktar',
        'close' => 'Kapat',
        'confirm' => 'Onayla',
        'back' => 'Geri',
        'next' => 'İleri',
        'previous' => 'Önceki',
        'start' => 'Başlat',
        'download' => 'İndir',
        'back_home' => 'Ana sayfaya dön',
    ],

    // Common labels
    'labels' => [
        'name' => 'İsim',
        'description' => 'Açıklama',
        'icon' => 'İkon',
        'color' => 'Renk',
        'order' => 'Sıra',
        'status' => 'Durum',
        'active' => 'Aktif',
        'inactive' => 'Pasif',
        'type' => 'Tür',
        'category' => 'Kategori',
        'language' => 'Dil',
        'date' => 'Tarih',
        'actions' => 'İşlemler',
        'total' => 'Toplam',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'book' => 'Kitap',
    ],

    // Messages
    'messages' => [
        'success' => 'İşlem başarılı',
        'error' => 'Bir hata oluştu',
        'confirm_delete' => 'Bu öğeyi silmek istediğinizden emin misiniz?',
        'no_results' => 'Sonuç bulunamadı',
        'loading' => 'Yükleniyor...',
        'saving' => 'Kaydediliyor...',
        'saved' => 'Kaydedildi',
        'deleted' => 'Silindi',
        'required_field' => 'Bu alan zorunludur',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Panel',
        'welcome' => 'Sadaa\'ya Hoşgeldiniz',
        'stats' => [
            'types' => 'Türler',
            'categories' => 'Kategoriler',
            'surahs' => 'Sureler',
            'ayahs' => 'Ayetler',
            'books' => 'Kitaplar',
            'languages' => 'Diller',
        ],
        'recent_imports' => 'Son İçe Aktarmalar',
        'quick_actions' => 'Hızlı İşlemler',
    ],

    // Books
    'books' => [
        'title' => 'Kitap Yönetimi',
        'add' => 'Kitap Ekle',
        'edit' => 'Kitap Düzenle',
        'book_title' => 'Kitap Başlığı',
        'author' => 'Yazar',
        'chapters' => 'Bölümler',
        'verses' => 'Ayetler',
    ],

    // Types
    'types' => [
        'title' => 'Tür Yönetimi',
        'add' => 'Tür Ekle',
        'edit' => 'Tür Düzenle',
        'no_types' => 'Mevcut tür yok',
    ],

    // Categories
    'categories' => [
        'title' => 'Kategori Yönetimi',
        'add' => 'Kategori Ekle',
        'edit' => 'Kategori Düzenle',
        'select_type' => 'Bir tür seçin',
        'no_categories' => 'Mevcut kategori yok',
    ],

    // Assignments
    'assignments' => [
        'title' => 'Ayet Atamaları',
        'assign' => 'Ata',
        'unassign' => 'Atamayı Kaldır',
        'select_surah' => 'Bir sure seçin',
        'select_category' => 'Bir kategori seçin',
        'assigned_verses' => 'Atanan Ayetler',
    ],

    // Import
    'import' => [
        'title' => 'Kuran İçe Aktar',
        'select_languages' => 'Dilleri seçin',
        'import_all' => 'Hepsini İçe Aktar',
        'import_selected' => 'Seçilenleri İçe Aktar',
        'progress' => 'İlerleme',
        'importing' => 'Aktarılıyor...',
        'complete' => 'İçe Aktarma Tamamlandı',
        'failed' => 'İçe Aktarma Başarısız',
    ],

    // History
    'history' => [
        'title' => 'Geçmiş',
        'date' => 'Tarih',
        'type' => 'Tür',
        'status' => 'Durum',
        'details' => 'Detaylar',
    ],

    // Settings
    'settings' => [
        'title' => 'Ayarlar',
        'general' => 'Genel',
        'languages' => 'Diller',
        'app_name' => 'Uygulama Adı',
        'app_tagline' => 'Slogan',
        'manage_languages' => 'Dilleri Yönet',
        'add_language' => 'Dil Ekle',
        'language_code' => 'Dil Kodu',
        'language_name' => 'Dil Adı',
        'rtl' => 'Sağdan Sola (RTL)',
        'source_language' => 'Kaynak Dil',
        'quran_edition' => 'Kuran Edisyonu',
    ],

    // Backup & Restore
    'backup' => [
        'title' => 'Yedekleme & Geri Yükleme',
        'export' => 'Veritabanını Dışa Aktar',
        'import' => 'Veritabanını Geri Yükle',
        'export_desc' => 'Veritabanınızın tam bir kopyasını indirin.',
        'import_desc' => 'Veritabanınızı bir yedek dosyasından geri yükleyin.',
        'format' => 'Format',
        'tables_included' => 'Tablolar',
        'download' => 'Yedeği İndir',
        'restore' => 'Geri Yükle',
        'select_file' => 'Bir dosya seçin',
        'accepted_formats' => 'Kabul edilen formatlar',
        'import_warning' => 'Uyarı: Bu işlem tüm mevcut verileri değiştirecektir!',
        'confirm_import' => 'Geri yüklemek istediğinizden emin misiniz? Tüm mevcut veriler silinecektir.',
        'confirm_delete' => 'Bu yedeği silmek istediğinizden emin misiniz?',
        'export_success' => 'Dışa aktarma başarılı',
        'export_error' => 'Dışa aktarma hatası',
        'import_success' => 'Geri yükleme başarılı',
        'import_error' => 'Geri yükleme hatası',
        'upload_error' => 'Dosya yükleme hatası',
        'invalid_format' => 'Geçersiz dosya formatı',
        'invalid_json' => 'Geçersiz veya bozuk JSON dosyası',
        'recent_backups' => 'Son Yedekler',
        'filename' => 'Dosya Adı',
        'size' => 'Boyut',
        'date' => 'Tarih',
    ],

    // Public interface
    'public' => [
        'tagline' => 'Ruh için bilgelik yankısı',
        'select_intention' => 'Bir niyet seçin...',
        'no_category' => 'Kategori mevcut değil',
        'change_theme' => 'Temayı değiştir',
        'verse' => 'Ayet',
        'surah' => 'Sure',
        'play' => 'Oynat',
        'pause' => 'Duraklat',
        'next_verse' => 'Sonraki ayet',
        'previous_verse' => 'Önceki ayet',
        'share' => 'Paylaş',
        'share_verse' => 'Ayeti paylaş',
        'copy' => 'Kopyala',
        'copied' => 'Kopyalandı!',
        'quran' => 'Kuran-ı Kerim',
        'read_quran' => 'Kuran Oku',
        'decrease_font' => 'Yazı boyutunu küçült',
        'increase_font' => 'Yazı boyutunu büyüt',
        'prev_surah' => 'Önceki sure',
        'next_surah' => 'Sonraki sure',
        'prev_page' => 'Önceki sayfa',
        'next_page' => 'Sonraki sayfa',
        'previous' => 'Önceki',
        'next' => 'Sonraki',
        'meccan' => 'Mekki',
        'medinan' => 'Medeni',
        'verses' => 'ayet',
        'about' => 'Hakkında',
        'github_link' => 'Bizi GitHub\'da bulun',
        'community_title' => 'Topluluk ve Katkılar',
        'community_text' => 'Bu proje açık kaynaklı ve topluluk odaklıdır. Çeviriler, kategoriler veya yeni fikirler önererek bize yardımcı olabilirsiniz.',
        'contribute_action' => 'GitHub\'da katkıda bulunun',
    ],

    // JavaScript translations (for frontend)
    'js' => [
        'select_intention' => 'Bir niyet seçin...',
        'no_category' => 'Kategori mevcut değil',
        'loading' => 'Yükleniyor...',
        'error' => 'Bir hata oluştu',
        'copied' => 'Kopyalandı!',
        'play' => 'Oynat',
        'pause' => 'Duraklat',
        'confirm_delete' => 'Silmek istediğinizden emin misiniz?',
        'meccan' => 'Mekki',
        'medinan' => 'Medeni',
        'verses' => 'ayet',
        'share_format' => 'Format',
        'share_theme' => 'Tema',
        'theme_dark' => 'Koyu',
        'theme_light' => 'Açık',
        'story' => 'Hikaye',
        'square' => 'Kare',
        'show_arabic' => 'Arapça metni göster',
    ],

    // Onboarding ve Rehberli Tur
    'onboarding' => [
        'welcome_title' => 'Sadaa\'ya Hoş Geldiniz',
        'welcome_text' => 'Sada, tefekkür için bir mekândır; ruhun Kur\'an hikmetinin yankısını dinlediği, ayetlerde kendisine rehberlik eden bir nur ve yolculuğunda ona eşlik eden bir anlam bulduğu yerdir.',
        'btn_start' => 'Tura Başla',
        'btn_skip' => 'Tanıtımı Atla',
        'btn_next' => 'İleri',
        'btn_prev' => 'Geri',
        'btn_done' => 'Bitir',
        'steps' => [
            'themes' => [
                'title' => 'Keşif Temaları',
                'text' => 'Sezgilerinizin sizi yönlendirmesine izin verin ve zihinsel durumunuzu ya da ruhsal ihtiyacınızı yansıtan temayı seçin.',
            ],
            'intentions' => [
                'title' => 'Tefekkür Eksenleri',
                'text' => 'Üzerinde tefekkür etmek istediğiniz ekseni seçin ve onu farklı bağlamlarda ele alan ayetleri keşfedin.',
            ],
            'display' => [
                'title' => 'Kişiselleştirme',
                'text' => 'Rahat bir okuma deneyimi için görünümü (açık/koyu) ve dili ayarlayın.',
            ],
            'github' => [
                'title' => 'Topluluk',
                'text' => 'Sadaa açık kaynaktır! Katkıda bulunmak veya iyileştirme önermek için GitHub\'da bize katılın.',
            ],
            'go' => [
                'title' => 'Başlamaya hazır mısınız?',
                'text' => 'Seçtiğiniz eksen doğrultusunda ayetleri tefekkür etmeye başlamak için buraya tıklayın.',
            ],
        ],
    ],

    // Surah Page Tour
    'surah_tour' => [
        'category_title' => 'Konu değiştir',
        'category_text' => 'İlgili ayetleri keşfetmek için ruhsal bir kategori seçin',
        'reader_title' => 'Ayet okuma',
        'reader_text' => 'Arapça ve çevirisiyle ayeti okuyun',
        'navigation_title' => 'Gezinme',
        'navigation_text' => 'Ayetler arasında gezinmek için okları kullanın',
        'copy_title' => 'Kopyala',
        'copy_text' => 'Ayet metnini panoya kopyalayın',
        'share_title' => 'Paylaş',
        'share_text' => 'Sosyal medyada paylaşmak için ayetin güzel bir görüntüsünü oluşturun',
        'read_mode_title' => 'Okuma modu',
        'read_mode_text' => 'Tam sureyi okumak için tam Kuran okuyucusunu açın',
    ],
];
