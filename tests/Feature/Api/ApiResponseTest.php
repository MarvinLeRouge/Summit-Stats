<?php

use App\Http\Traits\ApiResponse;
use Illuminate\Testing\TestResponse;

// Classe helper qui expose les méthodes protected du trait
class TestApiResponse
{
    use ApiResponse;

    public function callSuccess(mixed $data, int $status = 200) { return $this->success($data, $status); }
    public function callCreated(mixed $data)                     { return $this->created($data); }
    public function callNoContent()                              { return $this->noContent(); }
    public function callError(string $msg, int $status, array $errors = []) { return $this->error($msg, $status, $errors); }
}

it('success() returns 200 with data', function () {
    $test = TestResponse::fromBaseResponse((new TestApiResponse)->callSuccess(['key' => 'value']));
    $test->assertOk()->assertJson(['success' => true, 'data' => ['key' => 'value']]);
});

it('created() returns 201 with data', function () {
    $test = TestResponse::fromBaseResponse((new TestApiResponse)->callCreated(['id' => 1]));
    $test->assertCreated()->assertJson(['success' => true, 'data' => ['id' => 1]]);
});

it('noContent() returns 204', function () {
    $test = TestResponse::fromBaseResponse((new TestApiResponse)->callNoContent());
    $test->assertStatus(204);
});

it('error() returns correct status with message and errors', function () {
    $test = TestResponse::fromBaseResponse(
        (new TestApiResponse)->callError('Something went wrong', 400, ['field' => ['invalid']])
    );
    $test->assertBadRequest()->assertJson([
        'success' => false,
        'message' => 'Something went wrong',
        'errors'  => ['field' => ['invalid']],
    ]);
});