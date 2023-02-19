<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WP_Bootstrap_4
 */
global $withcomments;
$withcomments = 1;
 $requirement_id = isset( $_GET['p'] ) ? $_GET['p'] : '';
$post=get_post($requirement_id);
if (!$post){
	$post=wp_get_post_revision($requirement_id);
}

$comments = get_comments(array(
    'post_id' => $requirement_id,
    'status' => 'approve' 
));

setup_postdata($post);
get_header(); ?>

<?php
	$default_sidebar_position = ( comments_open() || get_comments_number() ) ?'right':'no' ;
	
?>

	<div class="container-fluid">
		<div class="row">

			<?php if ( $default_sidebar_position === 'no' ) : ?>
				<div class="col-md-12 wp-bp-content-width">
			<?php else : ?>
				<div class="col-md-8 wp-bp-content-width col-print-12">
			<?php endif; ?>

				<div id="primary" class="content-area">
					<main id="main" class="site-main">

					<?php
					//  the_post();
					 include 'reqview.php';

					?>

					</main><!-- #main -->
				</div><!-- #primary -->
			</div>
			<!-- /.col-md-8 -->

			<?php if ( $default_sidebar_position != 'no' ) : ?>
				<?php if ( $default_sidebar_position === 'right' ) : ?>
					<div class="col-md-4 wp-bp-sidebar-width col-print-12">
				<?php elseif ( $default_sidebar_position === 'left' ) : ?>
					<div class="col-md-4 order-md-first wp-bp-sidebar-width">
				<?php endif; ?>
						<?php get_sidebar(); ?>
				
            <aside id="secondary" class="widget-area sidebar-1-area mt-3r">
            <?php 
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :
							// var_dump($comments);
							comments_template();
						endif;
            ?>
            </aside>
					</div>
					<!-- /.col-md-4 -->
			<?php endif; ?>
		</div>
		<!-- /.row -->
	</div>
	<!-- /.container -->
  <style>
.comments-area .comments-title, .comments-area .comment-list > li, .comments-area .no-comments{
  padding:0.5em;
  margin:0px;
}

.comments-area .comment-list{
  margin:0px;
}
@media print {

.col-print-12{max-width:100%; flex:100% }
}
  </style>
<?php
get_footer();


