<?php
/**
 * Test case for the Snippet Manager.
 *
 * @package StarterSnippets\Tests
 */

namespace StarterSnippets\Tests;

use PHPUnit\Framework\TestCase;
use StarterSnippets\Helpers\Sanitization;
use StarterSnippets\Helpers\Validation;
use StarterSnippets\Core\Config;

class SnippetManagerTest extends TestCase {

    /**
     * Test that valid snippet data passes validation.
     */
    public function test_valid_snippet_data(): void {
        $data = [
            'title'    => 'Test Snippet',
            'code'     => 'echo "hello";',
            'language' => 'php',
            'location' => 'everywhere',
            'status'   => 'active',
        ];

        $errors = Validation::snippet( $data );
        $this->assertEmpty( $errors, 'Valid snippet data should produce no validation errors.' );
    }

    /**
     * Test that empty title fails validation.
     */
    public function test_empty_title_fails(): void {
        $data = [
            'title'    => '',
            'code'     => 'echo "hello";',
            'language' => 'php',
            'location' => 'everywhere',
            'status'   => 'active',
        ];

        $errors = Validation::snippet( $data );
        $this->assertNotEmpty( $errors );
    }

    /**
     * Test that empty code fails validation.
     */
    public function test_empty_code_fails(): void {
        $data = [
            'title'    => 'Test',
            'code'     => '',
            'language' => 'php',
            'location' => 'everywhere',
            'status'   => 'active',
        ];

        $errors = Validation::snippet( $data );
        $this->assertNotEmpty( $errors );
    }

    /**
     * Test enum sanitization.
     */
    public function test_enum_sanitization(): void {
        $result = Sanitization::enum( 'invalid', Config::LANGUAGES, 'php' );
        $this->assertEquals( 'php', $result );

        $result = Sanitization::enum( 'css', Config::LANGUAGES, 'php' );
        $this->assertEquals( 'css', $result );
    }

    /**
     * Test import data validation.
     */
    public function test_import_validation(): void {
        $valid = [
            [ 'title' => 'A', 'code' => 'x' ],
            [ 'title' => 'B', 'code' => 'y' ],
        ];
        $this->assertTrue( Validation::import_data( $valid ) );

        $invalid = [ [ 'code' => 'x' ] ]; // Missing title.
        $this->assertFalse( Validation::import_data( $invalid ) );

        $this->assertFalse( Validation::import_data( 'not an array' ) );
    }
}
