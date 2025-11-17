<?php get_header(); ?>

<div class="up-app">
  <header class="up-header">
    <div class="up-header-left">
      <div class="up-logo-circle">L</div>
      <div>
        <div class="up-store-name"><?php bloginfo( 'name' ); ?></div>
        <div class="up-store-sub">店頭受け取り専用・決済はレジで</div>
      </div>
    </div>
    <div class="up-header-right">
      BLOG
    </div>
  </header>

  <main class="up-main">
    <?php if ( have_posts() ) : ?>
      <div class="up-grid">
        <?php while ( have_posts() ) : the_post(); ?>
          <article class="up-card">
            <a href="<?php the_permalink(); ?>" class="up-card-link">
              <div class="up-card-image-wrap">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'medium', array( 'class' => 'up-card-image' ) ); ?>
                <?php else : ?>
                  <div class="up-card-image up-card-image--placeholder">
                    画像なし
                  </div>
                <?php endif; ?>
              </div>
              <div class="up-card-body">
                <h2 class="up-card-title"><?php the_title(); ?></h2>
                <div class="up-card-meta"><?php echo get_the_date(); ?></div>
                <div class="up-card-desc"><?php the_excerpt(); ?></div>
              </div>
            </a>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else : ?>
      <p class="up-empty">投稿がありません。</p>
    <?php endif; ?>
  </main>
</div>

<?php get_footer(); ?>
