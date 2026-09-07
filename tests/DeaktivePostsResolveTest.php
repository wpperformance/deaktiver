<?php

declare(strict_types=1);

namespace Deaktiver\Tests;

use Deaktiver\Deaktive\DeaktivePosts;
use ReflectionMethod;
use RuntimeException;

require_once __DIR__ . '/bootstrap.php';

/**
 * Unit tests for admin post type resolution when native posts are disabled.
 *
 * Run: php tests/DeaktivePostsResolveTest.php
 */
final class DeaktivePostsResolveTest
{
    private DeaktivePosts $deaktive;

    private int $failed = 0;

    private int $passed = 0;

    /**
     * Reset request globals and run every resolution case.
     */
    public function run(): int
    {
        $this->deaktive = new DeaktivePosts();

        $this->test_get_post_type_on_post_new_for_cpt();
        $this->test_post_body_post_type_on_classic_save_without_query_string();
        $this->test_post_id_of_cpt_without_post_type_uses_get_post_type();
        $this->test_get_post_id_of_cpt_on_edit_screen();
        $this->test_post_new_without_post_type_is_native_post();
        $this->test_edit_php_without_post_type_is_native_post();
        $this->test_native_post_id_on_post_php_stays_post();
        $this->test_native_post_save_with_post_type_in_body_stays_post();
        $this->test_get_post_type_wins_over_post_body();
        $this->test_request_post_fallback_for_cpt_id();
        $this->test_post_new_save_body_matches_classic_save();

        echo "{$this->passed} passed, {$this->failed} failed\n";

        return $this->failed === 0 ? 0 : 1;
    }

    /**
     * Reset GET/POST/REQUEST and the get_post_type() test map.
     */
    private function resetRequest(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $GLOBALS['pagenow'] = '';
        $GLOBALS['deaktiver_test_post_types'] = [];
    }

    /**
     * Invoke the private resolver against the current request globals.
     */
    private function resolve(): string
    {
        $method = new ReflectionMethod(DeaktivePosts::class, 'resolve_admin_post_type');
        $method->setAccessible(true);

        return $method->invoke($this->deaktive);
    }

    /**
     * Record a passing or failing assertion.
     */
    private function assertSame(string $expected, string $actual, string $message): void
    {
        if ($expected === $actual) {
            $this->passed++;
            echo "PASS {$message}\n";

            return;
        }

        $this->failed++;
        echo "FAIL {$message}: expected {$expected}, got {$actual}\n";
    }

    private function test_get_post_type_on_post_new_for_cpt(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post-new.php';
        $_GET['post_type'] = 'product';

        $this->assertSame('product', $this->resolve(), 'GET post_type on post-new.php for a CPT');
    }

    private function test_post_body_post_type_on_classic_save_without_query_string(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_POST['post_type'] = 'product';

        $this->assertSame('product', $this->resolve(), 'POST post_type on post.php without query string');
    }

    private function test_post_id_of_cpt_without_post_type_uses_get_post_type(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_POST['post_ID'] = '42';
        $GLOBALS['deaktiver_test_post_types'][42] = 'product';

        $this->assertSame('product', $this->resolve(), 'POST post_ID of a CPT without post_type');
    }

    private function test_get_post_id_of_cpt_on_edit_screen(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_GET['post'] = '42';
        $GLOBALS['deaktiver_test_post_types'][42] = 'product';

        $this->assertSame('product', $this->resolve(), 'GET post.php?post={id} of a CPT');
    }

    private function test_post_new_without_post_type_is_native_post(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post-new.php';

        $this->assertSame('post', $this->resolve(), 'post-new.php without post_type (block)');
    }

    private function test_edit_php_without_post_type_is_native_post(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'edit.php';

        $this->assertSame('post', $this->resolve(), 'edit.php without post_type (block)');
    }

    private function test_native_post_id_on_post_php_stays_post(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_GET['post'] = '7';
        $GLOBALS['deaktiver_test_post_types'][7] = 'post';

        $this->assertSame('post', $this->resolve(), 'post.php of a native post (block)');
    }

    private function test_native_post_save_with_post_type_in_body_stays_post(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_POST['post_type'] = 'post';
        $_POST['post_ID'] = '7';
        $GLOBALS['deaktiver_test_post_types'][7] = 'post';

        $this->assertSame('post', $this->resolve(), 'POST save of a native post (block)');
    }

    private function test_get_post_type_wins_over_post_body(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_GET['post_type'] = 'product';
        $_POST['post_type'] = 'post';

        $this->assertSame('product', $this->resolve(), 'GET post_type wins over POST');
    }

    private function test_request_post_fallback_for_cpt_id(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_REQUEST['post'] = '99';
        $GLOBALS['deaktiver_test_post_types'][99] = 'book';

        $this->assertSame('book', $this->resolve(), 'REQUEST post ID fallback for a CPT');
    }

    private function test_post_new_save_body_matches_classic_save(): void
    {
        $this->resetRequest();
        $GLOBALS['pagenow'] = 'post.php';
        $_POST['post_type'] = 'product';
        $_POST['post_ID'] = '15';
        $GLOBALS['deaktiver_test_post_types'][15] = 'product';

        $this->assertSame('product', $this->resolve(), 'POST from post-new.php to post.php for a CPT');
    }
}

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('These tests must run from the CLI.');
}

exit((new DeaktivePostsResolveTest())->run());
