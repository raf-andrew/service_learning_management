<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CodespacesTestTrait;

class FileUploadTest extends TestCase
{
    use CodespacesTestTrait, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCodespacesTest();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        $this->tearDownCodespacesTest();
        parent::tearDown();
    }

    /**
     * Test that files can be uploaded
     *
     * @return void
     */
    public function test_file_can_be_uploaded()
    {
        $this->addTestStep('file_upload', 'running');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post('/api/upload', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        Storage::disk('local')->assertExists('uploads/' . $file->hashName());
        $this->addTestStep('file_upload', 'completed');
        $this->linkTestToChecklist('file-upload');
    }

    /**
     * Test that file validation works
     *
     * @return void
     */
    public function test_file_validation()
    {
        $this->addTestStep('file_validation', 'running');
        $file = UploadedFile::fake()->create('test.txt', 0);

        $response = $this->post('/api/upload', [
            'file' => $file
        ]);

        $response->assertStatus(422);
        Storage::disk('local')->assertMissing('uploads/' . $file->hashName());
        $this->addTestStep('file_validation', 'completed');
        $this->linkTestToChecklist('file-validation');
    }

    /**
     * Test that file size limits work
     *
     * @return void
     */
    public function test_file_size_limits()
    {
        $this->addTestStep('file_size_limit', 'running');
        $file = UploadedFile::fake()->create('test.txt', 10241); // 10MB + 1KB

        $response = $this->post('/api/upload', [
            'file' => $file
        ]);

        $response->assertStatus(422);
        Storage::disk('local')->assertMissing('uploads/' . $file->hashName());
        $this->addTestStep('file_size_limit', 'completed');
        $this->linkTestToChecklist('file-size-limit');
    }

    /**
     * Test that file type validation works
     *
     * @return void
     */
    public function test_file_type_validation()
    {
        $this->addTestStep('file_type_validation', 'running');
        $file = UploadedFile::fake()->create('test.exe', 100);

        $response = $this->post('/api/upload', [
            'file' => $file
        ]);

        $response->assertStatus(422);
        Storage::disk('local')->assertMissing('uploads/' . $file->hashName());
        $this->addTestStep('file_type_validation', 'completed');
        $this->linkTestToChecklist('file-type-validation');
    }

    /**
     * Test that multiple files can be uploaded
     *
     * @return void
     */
    public function test_multiple_file_upload()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg')
        ];

        $response = $this->post('/api/upload-multiple', [
            'files' => $files
        ]);

        $response->assertStatus(200);
        foreach ($files as $file) {
            Storage::disk('local')->assertExists('uploads/' . $file->hashName());
        }
    }

    /**
     * Test that file metadata is stored
     *
     * @return void
     */
    public function test_file_metadata_storage()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post('/api/upload', [
            'file' => $file,
            'description' => 'Test file'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('files', [
            'filename' => $file->hashName(),
            'description' => 'Test file'
        ]);
    }
} 