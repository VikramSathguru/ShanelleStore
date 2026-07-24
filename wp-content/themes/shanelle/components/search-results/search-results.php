<?php
/**
 * Search results component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\SearchResults;

defined( 'ABSPATH' ) || exit;

if ( SearchResults::is_idle_mode() ) :
	?>
	<div class="search-results search-results--idle" data-shanelle-search-idle>
		<?php SearchResults::render_recent_searches_shell(); ?>
		<?php SearchResults::render_popular_searches(); ?>
	</div>
	<?php
	return;
endif;

if ( ! SearchResults::has_results() ) :
	SearchResults::render_empty();
	return;
endif;
?>
<div class="search-results" data-shanelle-search-results-panel>
	<?php SearchResults::render_categories(); ?>
	<?php SearchResults::render_collections(); ?>
	<?php SearchResults::render_products(); ?>
	<?php SearchResults::render_view_all(); ?>
</div>
