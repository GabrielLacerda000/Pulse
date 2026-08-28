<?php

use App\Actions\MaskPhoneAction;

beforeEach(function () {
    $this->action = new MaskPhoneAction;
});

test('a normalized phone number is masked to country code, stars, and last four digits', function () {
    expect($this->action->execute('5581999998888'))->toBe('+55***8888');
});

test('a formatted phone number is stripped before masking', function () {
    expect($this->action->execute('+55 (81) 99999-8888'))->toBe('+55***8888');
});

test('a short number keeps only stars visible', function () {
    expect($this->action->execute('123'))->toBe('+***');
});

test('null phone masks to null', function () {
    expect($this->action->execute(null))->toBeNull();
});

test('blank phone masks to null', function () {
    expect($this->action->execute('   '))->toBeNull();
});
