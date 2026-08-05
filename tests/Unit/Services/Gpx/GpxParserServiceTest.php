<?php

use App\Exceptions\GpxParseException;
use App\Services\Gpx\GpxParserService;
use Carbon\Carbon;

beforeEach(function () {
    $this->parser = new GpxParserService;
    $this->fixturePath = base_path('tests/Fixtures/gpx');
});

it('extracts trackpoints from a valid GPX file', function () {
    $points = $this->parser->parse("{$this->fixturePath}/simple_track.gpx");

    expect($points)->toHaveCount(3);
    expect($points[0])->toMatchArray([
        'lat' => 45.8326,
        'lon' => 6.8652,
        'ele' => 1034.0,
    ]);
    expect($points[0]['time'])->toBeInstanceOf(Carbon::class);
});

it('throws an exception for an invalid GPX file', function () {
    $this->parser->parse("{$this->fixturePath}/invalid.gpx");
})->throws(GpxParseException::class);

it('throws an exception if file does not exist', function () {
    $this->parser->parse("{$this->fixturePath}/nonexistent.gpx");
})->throws(GpxParseException::class);

it('handles GPX without elevation data', function () {
    $points = $this->parser->parse("{$this->fixturePath}/no_elevation.gpx");

    expect($points)->toHaveCount(2);
    expect($points[0]['ele'])->toBeNull();
});

it('handles GPX without time data', function () {
    $points = $this->parser->parse("{$this->fixturePath}/no_time.gpx");

    expect($points)->toHaveCount(2);
    expect($points[0]['time'])->toBeNull();
});

it('returns correct structure for each trackpoint', function () {
    $points = $this->parser->parse("{$this->fixturePath}/simple_track.gpx");

    expect($points[0])->toHaveKeys(['lat', 'lon', 'ele', 'time']);
});

it('throws exception when GPX has no trackpoints', function () {
    $gpx = '<?xml version="1.0"?><gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1"><trk><trkseg></trkseg></trk></gpx>';
    file_put_contents(base_path('tests/Fixtures/gpx/empty_track.gpx'), $gpx);

    expect(fn () => $this->parser->parse(base_path('tests/Fixtures/gpx/empty_track.gpx')))
        ->toThrow(GpxParseException::class);
});

it('throws exception for a single point GPX', function () {
    expect(fn () => $this->parser->parse(base_path('tests/Fixtures/gpx/single_point.gpx')))
        ->toThrow(GpxParseException::class, 'La trace GPX doit contenir au moins 2 points.');
});

it('rejects a GPX file containing a DOCTYPE declaration', function () {
    $gpx = <<<'XML'
    <?xml version="1.0"?>
    <!DOCTYPE gpx [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
    <gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">
        <trk><trkseg>
            <trkpt lat="45.83" lon="6.86"></trkpt>
            <trkpt lat="45.84" lon="6.87"></trkpt>
        </trkseg></trk>
    </gpx>
    XML;
    file_put_contents(base_path('tests/Fixtures/gpx/xxe_attempt.gpx'), $gpx);

    expect(fn () => $this->parser->parse(base_path('tests/Fixtures/gpx/xxe_attempt.gpx')))
        ->toThrow(GpxParseException::class);
});
