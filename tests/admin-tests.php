<?php
class Admin_Tests extends PHPUnit_Framework_TestCase {

	public $seadmin;

	static function setUpBeforeClass() {
		require_once __DIR__ . '/../views/options.php';
	}

	function setUp() {
		WP_Mock::setUp();
	}

	function tearDown() {
		WP_Mock::tearDown();
	}

	function test_setup() {
		WP_Mock::wpFunction( 'get_locale', array(
			'return' => '',
		));

		$this->seadmin = new se_admin();

		WP_Mock::expectActionAdded( 'admin_head', array( $this->seadmin, 'se_options_style' ) );
		WP_Mock::expectActionAdded( 'admin_menu', array( $this->seadmin, 'se_add_options_panel' ) );

		$this->seadmin->se_admin();
	}

    function test_add_options_link() {
        $this->markTestIncomplete( 'Make sure page link is added correctly' );
    }

    function test_options_page_display() {
        $this->markTestIncomplete( 'Test base output with no options selected' );
    }

    function test_options_page_disply_wp_ver_23() {
        $this->markTestIncomplete( 'Test options output with $wp_version set to 2.3' );
    }

    function test_options_page_disply_wp_ver_25() {
        $this->markTestIncomplete( 'Test options output with $wp_version set to 2.5' );
    }

    function test_options_page_update() {
        $this->markTestIncomplete( 'Test update with post params' );
    }

    function test_options_page_reset() {
        $this->markTestIncomplete( 'Test reset action' );
    }
}