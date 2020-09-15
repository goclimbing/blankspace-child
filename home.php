<?php get_header(); ?>


<section id="content" role="main" class="home-wrap">

	<div class="home-wrap-articles">


	<?php if (have_posts() ) : while (have_posts() ) : the_post(); ?>

		<article <?php post_class('post'); ?>>
			<h1 class="main-h1"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
			<p class="blog-date">Posted <?php the_time('F j, Y'); ?></p>

			<?php if(get_the_post_thumbnail() ) : ?>
				<div class="blog-featured-image-div">
					<?php the_post_thumbnail('medium'); ?>
				</div>
			<?php endif; ?>


			<p><?php echo strip_tags(the_excerpt()); ?></p>

			<p>Categories: <?php the_category(', '); ?></p>

		</article>

		
	<?php endwhile; else : ?>

	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
	<?php endif; ?>

	<hr>

	<div class="blog-pagination-wrap">
		<div class="nav-previous"><?php next_posts_link('&lt; Older posts'); ?></div>
		<div class="nav-next"><?php previous_posts_link('Newer posts &gt;'); ?></div>
	</div>
</div><!--home-wrap-articles-->


<div class="home-wrap-sidebar">
<?php get_sidebar();?>
</div>

</section>



<?php get_footer();?>
