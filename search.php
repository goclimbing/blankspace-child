<?php get_header(); ?>



<section id="content" role="main" class="sub-wrap">

<h1>Search Results</h1>
<hr>

	<?php 
		$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
	if (have_posts() ) : while (have_posts() ) : the_post(); 

	?>
		

		<article class="main-article clear">

				<h1><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h1>
				<?php if ( has_post_thumbnail() ) : ?>
				
					<?php the_post_thumbnail('thumbnail', array( 'class' => 'search-thumbnail' ) );?>
				<?php endif; ?>
				<p><?php the_excerpt(); ?></p>

		</article>



	<?php endwhile; ?>


	<?php
global $wp_query;

$big = 999999999; // need an unlikely integer

echo paginate_links( array(
	'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
	'format' => '?paged=%#%',
	'current' => max( 1, get_query_var('paged') ),
	'total' => $wp_query->max_num_pages
) );
?>


	<?php else : ?>

	


	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
	<?php endif; ?>

	


</section>



<?php get_footer();?>
