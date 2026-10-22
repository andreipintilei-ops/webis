<?php

use App\Support\Legacy\AttachmentMetadata;

const UPLOADS = 'https://www.webis.ro/wp-content/uploads';

it('expands every size into a URL in the original\'s directory', function () {
    $metadata = serialize([
        'width' => 2560,
        'height' => 1571,
        'file' => '2024/03/404-3-scaled.jpg',
        'sizes' => [
            'medium' => ['file' => '404-3-300x184.jpg', 'width' => 300, 'height' => 184, 'mime-type' => 'image/jpeg'],
            'thumbnail' => ['file' => '404-3-150x150.jpg', 'width' => 150, 'height' => 150, 'mime-type' => 'image/jpeg'],
            // Two sizes can share one file; it is listed once.
            'woocommerce_thumbnail' => ['file' => '404-3-150x150.jpg', 'width' => 150, 'height' => 150],
        ],
        'image_meta' => ['caption' => ''],
        'original_image' => '404-3.jpg',
    ]);

    $derivatives = AttachmentMetadata::derivatives($metadata, '2024/03/404-3-scaled.jpg', UPLOADS);

    expect(array_column($derivatives, 'url'))->toBe([
        UPLOADS.'/2024/03/404-3-300x184.jpg',
        UPLOADS.'/2024/03/404-3-150x150.jpg',
        UPLOADS.'/2024/03/404-3.jpg',
    ])
        ->and($derivatives[0])->toMatchArray(['size' => 'medium', 'file' => '2024/03/404-3-300x184.jpg', 'width' => 300, 'height' => 184])
        ->and($derivatives[2])->toMatchArray(['size' => 'original_image', 'width' => null]);
});

it('adds the image editor\'s backup copies', function () {
    $metadata = serialize(['file' => '2021/05/logo-e1620000000000.png', 'sizes' => []]);
    $backups = serialize(['full-orig' => ['width' => 1000, 'height' => 675, 'file' => 'logo.png']]);

    expect(AttachmentMetadata::derivatives($metadata, '2021/05/logo-e1620000000000.png', UPLOADS, $backups))
        ->toBe([['size' => 'backup:full-orig', 'file' => '2021/05/logo.png', 'url' => UPLOADS.'/2021/05/logo.png', 'width' => 1000, 'height' => 675]]);
});

it('works for files at the uploads root and for missing metadata', function () {
    $metadata = serialize(['file' => 'logo.png', 'sizes' => ['thumbnail' => ['file' => 'logo-150x150.png']]]);

    expect(array_column(AttachmentMetadata::derivatives($metadata, 'logo.png', UPLOADS), 'url'))->toBe([UPLOADS.'/logo-150x150.png'])
        ->and(AttachmentMetadata::derivatives(null, 'video.mp4', UPLOADS))->toBe([])
        ->and(AttachmentMetadata::derivatives('not serialised', 'x.jpg', UPLOADS))->toBe([]);
});

it('never unserialises objects', function () {
    $value = serialize(['sizes' => new ArrayObject([1])]);

    expect(AttachmentMetadata::unserialise($value)['sizes'])->toBeInstanceOf(__PHP_Incomplete_Class::class);
});
