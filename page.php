<?php get_header(); ?>



<section id="content" role="main" class="page-wrap">


	<?php if (have_posts() ) : while (have_posts() ) : the_post(); ?>

		<article <?php post_class('post'); ?>>

			


			<?php the_content(); ?>

			<?php endwhile; else : ?>

			<p><?php _e('Sorry, no pages found.'); ?></p>
			<?php endif; ?>

		</article>


</section>



<?php get_footer();?>
