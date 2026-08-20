<?php

use App\Actions\NormalizePhoneAction;

beforeEach(function () {
    $this->action = new NormalizePhoneAction;
});

test('formatted input is stripped of spaces, parentheses and hyphens and gets the default country code', function () {
    expect($this->action->execute('(81) 3482-7554'))->toBe('558134827554');
});

test('a number missing the country code gets the default country code applied', function () {
    expect($this->action->execute('81999999999'))->toBe('5581999999999');
});

test('an already normalized number is left unchanged', function () {
    expect($this->action->execute('5581999999999'))->toBe('5581999999999');
});

test('null phone normalizes to null', function () {
    expect($this->action->execute(null))->toBeNull();
});

test('blank phone normalizes to null', function () {
    expect($this->action->execute('   '))->toBeNull();
});

test('a phone missing the area code is invalid and normalizes to null', function () {
    expect($this->action->execute('7554'))->toBeNull();
});
