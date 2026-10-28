<?php

use App\Support\Legacy\UploadsArchive;

it('knows the upload files listed in the archive, relative to uploads', function () {
    $archive = new UploadsArchive([
        'public_html/wp-content/uploads/',
        'public_html/wp-content/uploads/2024/07/',
        'public_html/wp-content/uploads/2024/07/zyanya.jpg',
        'public_html/wp-content/uploads/2024/07/zyanya-300x200.jpg',
        'public_html/wp-content/themes/x/screenshot.png',
    ]);

    expect($archive->count())->toBe(2)
        ->and($archive->has('2024/07/zyanya.jpg'))->toBeTrue()
        ->and($archive->has('/2024/07/zyanya-300x200.jpg'))->toBeTrue()
        ->and($archive->has('2024/07/lipsa.jpg'))->toBeFalse()
        ->and($archive->has('2024/07'))->toBeFalse();
});

it('reads the index of a zip without extracting it', function () {
    $path = sys_get_temp_dir().'/uploads-archive-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('public_html/wp-content/uploads/2021/04/seo.jpg', 'jpg');
    $zip->addFromString('public_html/index.php', '<?php');
    $zip->close();

    try {
        $archive = UploadsArchive::fromZip($path);

        expect($archive->count())->toBe(1)
            ->and($archive->has('2021/04/seo.jpg'))->toBeTrue();
    } finally {
        unlink($path);
    }
});
