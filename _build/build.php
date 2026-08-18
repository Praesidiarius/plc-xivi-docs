<?php

/*
 * This file is part of the Xivi documentation.
 *
 * (c) Praesidiarius <praesidiarius@proton.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

/*
 * Build the documentation into `_build/output/`.
 *
 * **Written here rather than copied out of `symfony/symfony-docs`**, and the
 * distinction matters. `symfony-tools/docs-builder` is MIT and is installed from
 * Packagist, which is a clean grant. The `_build/` directory of Symfony's own
 * documentation repository is a different thing: those files live inside a
 * repository whose stated licence is CC BY-SA 3.0 — a *content* licence sitting
 * over what is really code — and inheriting that ambiguity for the sake of not
 * writing thirty lines would be a poor trade. So this is ours.
 *
 * The same reasoning is why every word of the documentation itself is written
 * rather than adapted: copyright covers expression, so reading Symfony's
 * documentation and then writing our own about our own product produces an
 * independent work. Their structure is an idea and we have borrowed it happily;
 * their sentences are not, and we have taken none.
 *
 * Usage:
 *
 *     bin/docs build        # once, into _build/output
 *     bin/docs serve        # build, then serve on :8080 and rebuild on demand
 *
 * Both run inside a container (see compose.yaml); there is no PHP on the host,
 * which is the same rule the main repository works under.
 */

use SymfonyDocsBuilder\BuildConfig;
use SymfonyDocsBuilder\DocBuilder;

require __DIR__ . '/vendor/autoload.php';

$root = \dirname(__DIR__);
$output = __DIR__ . '/output';

/*
 * `setSymfonyVersion()` is not about Symfony here — the builder uses it to
 * resolve the `:doc:` and version-dependent directives it was written for, and
 * it insists on a value. Pinning it to the version Xivi itself runs on keeps the
 * two from drifting for no reason, and nothing in these pages depends on it.
 */
$config = (new BuildConfig())
    ->setSymfonyVersion('7.4')
    ->setContentDir($root)
    /*
     * **`_build` is inside the content directory and must be kept out of it.**
     *
     * The builder walks the content directory for `.rst` files, and `_build`
     * holds `vendor/` — which contains the RST parser, which ships its own test
     * fixtures, which are `.rst` files referencing includes that do not exist
     * here. Without this the first build dies inside somebody else's test suite,
     * several frames deep, with a message about `/subdir/include.rst.inc`.
     *
     * The directory is named `_build` rather than `build` for the same reason
     * Symfony names theirs that way: a leading underscore is the convention for
     * "not content", and it reads as deliberate to anybody who arrives later.
     */
    ->setOutputDir($output)
    ->setImagesDir($output . '/_images')
    ->setImagesPublicPrefix('_images')
    ->setTheme('rtd');

/*
 * Called on its own rather than in the chain above, because it is the one setter
 * in `BuildConfig` that does not return `$this` — every neighbour does, so a
 * chained call fails on `null` at the *next* method and points at the wrong
 * line. Left as its own statement with this note so nobody tidies it back in.
 */
$config->setExcludedPaths(['_build']);

$result = (new DocBuilder())->build($config);

if (!$result->isSuccessful()) {
    // The builder collects every problem rather than stopping at the first, so
    // print the lot: a broken reference and a missing file are usually the same
    // rename, and seeing them together is what makes that obvious.
    fwrite(\STDERR, "\nThe documentation did not build.\n\n");
    fwrite(\STDERR, $result->getErrorTrace() . "\n");

    exit(1);
}

$pages = iterator_count(new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($output, \FilesystemIterator::SKIP_DOTS),
));

printf("Built %d files into %s\n", $pages, $output);
