<?php

use App\Services\WorkLocationZoneResolver;

it('matches configured location keywords without case sensitivity', function () {
    config()->set('mitra.zones.zona-merah.location_keywords', ['Workshop PLTU Tanjung Jati']);

    expect(app(WorkLocationZoneResolver::class)->resolve('workshop pltu tanjung jati, Jepara'))
        ->toBe('zona-merah');
});

it('returns no zone when the work location is not mapped', function () {
    expect(app(WorkLocationZoneResolver::class)->resolve('Lokasi Proyek Baru'))
        ->toBeNull();
});

it('returns no zone when a location matches more than one zone', function () {
    config()->set('mitra.zones.zona-merah.location_keywords', ['Workshop']);
    config()->set('mitra.zones.zona-biru.location_keywords', ['Workshop PLTU']);

    expect(app(WorkLocationZoneResolver::class)->resolve('Workshop PLTU Tanjung Jati'))
        ->toBeNull();
});


it('detects Parikesit as ZONA MERAH from the default location mapping', function () {
    expect(app(WorkLocationZoneResolver::class)->resolve('Area PARIKESIT, Kalimantan Timur'))
        ->toBe('zona-merah');
});
