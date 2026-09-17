<?php
/**
 * Tests for the Project meta read model.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\Capabilities;
use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;
use WP_UnitTestCase;

final class ProjectMetaTest extends WP_UnitTestCase {

	private ProjectMeta $meta;

	public function set_up(): void {
		parent::set_up();
		$this->meta = new ProjectMeta();
	}

	/**
	 * @dataProvider stack_provider
	 *
	 * @param string   $input    Raw stored value.
	 * @param string[] $expected Normalized list.
	 */
	public function test_split_stack_normalizes_input( string $input, array $expected ): void {
		$this->assertSame( $expected, $this->meta->split_stack( $input ) );
	}

	/**
	 * @return array<string, array{0:string, 1:string[]}>
	 */
	public static function stack_provider(): array {
		return array(
			'empty string'        => array( '', array() ),
			'whitespace only'     => array( '   ', array() ),
			'single value'        => array( 'React', array( 'React' ) ),
			'trims and splits'    => array( 'React, WordPress ,PHP', array( 'React', 'WordPress', 'PHP' ) ),
			'drops empty entries' => array( 'React,,PHP,', array( 'React', 'PHP' ) ),
			'deduplicates'        => array( 'React, PHP, React', array( 'React', 'PHP' ) ),
		);
	}

	/**
	 * @dataProvider bool_provider
	 *
	 * @param mixed $input    Raw value.
	 * @param bool  $expected Coerced boolean.
	 */
	public function test_sanitize_bool_coerces_truthy_values( $input, bool $expected ): void {
		$this->assertSame( $expected, ProjectMeta::sanitize_bool( $input ) );
	}

	/**
	 * @return array<string, array{0:mixed, 1:bool}>
	 */
	public static function bool_provider(): array {
		return array(
			'string 1'     => array( '1', true ),
			'string true'  => array( 'true', true ),
			'boolean true' => array( true, true ),
			'string 0'     => array( '0', false ),
			'empty string' => array( '', false ),
			'string false' => array( 'false', false ),
		);
	}

	public function test_read_returns_typed_values_from_saved_post(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => ProjectPostType::POST_TYPE ) );

		update_post_meta( $post_id, ProjectMeta::ROLE, 'Senior Full-stack' );
		update_post_meta( $post_id, ProjectMeta::STACK, 'React, WordPress, PHP' );
		update_post_meta( $post_id, ProjectMeta::REPO_URL, 'https://example.com/repo' );
		update_post_meta( $post_id, ProjectMeta::FEATURED, '1' );

		$result = $this->meta->read( $post_id );

		$this->assertSame( 'Senior Full-stack', $result['role'] );
		$this->assertSame( array( 'React', 'WordPress', 'PHP' ), $result['stack'] );
		$this->assertSame( 'https://example.com/repo', $result['repoUrl'] );
		$this->assertTrue( $result['featured'] );
	}

	public function test_can_edit_meta_enforces_per_post_capability(): void {
		// Project capabilities are granted only to administrator/editor.
		Capabilities::add();
		$editor_id     = self::factory()->user->create( array( 'role' => 'editor' ) );
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$project = self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		// A subscriber cannot edit the project's meta.
		wp_set_current_user( $subscriber_id );
		$this->assertFalse( $this->meta->can_edit_meta( $project ) );

		// An editor (granted the project capabilities) can.
		wp_set_current_user( $editor_id );
		$this->assertTrue( $this->meta->can_edit_meta( $project ) );
	}

	public function test_read_memoizes_within_an_instance(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => ProjectPostType::POST_TYPE ) );
		update_post_meta( $post_id, ProjectMeta::ROLE, 'First' );

		// First read populates the per-instance memo.
		$this->assertSame( 'First', $this->meta->read( $post_id )['role'] );

		// Underlying value changes, but the same instance keeps its memoized copy.
		update_post_meta( $post_id, ProjectMeta::ROLE, 'Second' );
		$this->assertSame( 'First', $this->meta->read( $post_id )['role'] );

		// A fresh instance (as on a new request) sees the current value.
		$this->assertSame( 'Second', ( new ProjectMeta() )->read( $post_id )['role'] );
	}
}
