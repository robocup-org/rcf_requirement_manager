<?php
include_once 'acfview.php'
?>
<article id="post-<?php the_ID(); ?>" class="card mt-3r ">
	<div class="card-body">

		<?php if ( is_sticky() ) : ?>
			<span class="oi oi-bookmark wp-bp-sticky text-muted" title="<?php echo esc_attr__( 'Sticky Post', 'wp-bootstrap-4' ); ?>"></span>
		<?php endif; ?>
		<header class="entry-header">
			<?php
			// if ( is_singular() ) :
				the_title( '<h1 class="entry-title card-title h2">', '</h1>' );
			// else :
				// the_title( '<h2 class="entry-title card-title h3"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark" class="text-dark">', '</a></h2>' );
			// endif;
			
			?>
			<div class="entry-meta text-muted">
				State: <?php echo get_post_status()=="inherit"?"incomplete revision": get_post_status()?> <span style='float:right'>Date: <?php echo get_the_date() ?> </span>
			</div><!-- .entry-meta -->
			
		</header><!-- .entry-header -->

		<?php wp_bootstrap_4_post_thumbnail(); ?>


			<div class="entry-content">
				<?php
				$fields = acf_get_fields('Req_group');
				

				// var_dump($fields);
				// Loop through each field object and display its label and value
				if ($fields) {
					foreach ($fields as $key =>$field) {
						$fo=get_field_object($field['name']);
						$value='';
						if ($fo)
							$value=$fo['value'];
						
						acf_render($field,$value)	;
						// var_dump($field);
						
					}
				}
				if ($has_accr!==""){
					echo "</div></div>";
					$has_accr='';
				}
				?>
			</div><!-- .entry-content -->

	</div>
	<!-- /.card-body -->

	
	

</article><!-- #post-<?php the_ID(); ?> -->
