<?php
/**
 * 商品カテゴリー一覧（すべて表示 → で来るページ）
 */

get_header();

$term = get_queried_object();
?>

<div class="up-shell">

  <!-- 青いヘッダー（トップと同じ） -->
  <header class="up-global-header">
    <div class="up-global-inner">
      <div class="up-global-left">
        <div class="up-global-logo">L</div>
        <div class="up-global-store">
          <div class="up-global-store-name"><?php bloginfo( 'name' ); ?></div>
          <div class="up-global-store-meta">店頭受け取り専用・決済はレジにて</div>
        </div>
      </div>
      <div class="up-global-right">
        <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="up-global-search">
          <span class="up-global-search-icon">🔍</span>
          <input
            type="search"
            name="s"
            class="up-global-search-input"
            placeholder="商品名・キーワードで検索"
            value="<?php echo esc_attr( get_search_query() ); ?>"
          >
          <input type="hidden" name="post_type" value="item">
        </form>
      </div>
    </div>
  </header>

  <div class="up-layout up-layout--cat">

    <!-- 左サイドバー（今のカテゴリ位置がわかるように） -->
    <aside class="up-sidebar">
      <div class="up-sidebar-title">カテゴリー</div>
      <nav class="up-sidebar-nav">
        <a href="<?php echo esc_url( get_post_type_archive_link( 'item' ) ); ?>"
           class="up-sidebar-link up-sidebar-link--all">
          すべての商品
        </a>
        <?php
        $all_terms = get_terms(
          array(
            'taxonomy'   => 'item_category',
            'hide_empty' => false,
          )
        );
        if ( ! is_wp_error( $all_terms ) ) :
          foreach ( $all_terms as $t ) :
            $active = ( $t->term_id === $term->term_id ) ? ' up-sidebar-link--active' : '';
            ?>
            <a href="<?php echo esc_url( get_term_link( $t ) ); ?>"
               class="up-sidebar-link<?php echo esc_attr( $active ); ?>">
              <?php echo esc_html( $t->name ); ?>
            </a>
            <?php
          endforeach;
        endif;
        ?>
      </nav>
    </aside>

    <main class="up-main-area">

      <!-- カテゴリタイトル -->
      <header class="up-cat-header">
        <h1 class="up-cat-title"><?php single_term_title(); ?></h1>
        <?php if ( ! empty( $term->description ) ) : ?>
          <p class="up-cat-desc"><?php echo esc_html( $term->description ); ?></p>
        <?php endif; ?>
      </header>

      <?php if ( have_posts() ) : ?>
        <!-- 商品グリッド（PC3〜4列 / スマホ2列） -->
        <div class="up-cat-grid">
          <?php
          while ( have_posts() ) :
            the_post();
            ?>
            <article class="up-product-card up-product-card--cat">
              <div class="up-product-image-wrap">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'medium', array( 'class' => 'up-product-image' ) ); ?>
                <?php else : ?>
                  <div class="up-product-image up-product-image--placeholder">
                    画像なし
                  </div>
                <?php endif; ?>
              </div>

              <div class="up-product-body">
                <h2 class="up-product-name">
                  <a href="<?php the_permalink(); ?>" class="up-product-name-link">
                    <?php the_title(); ?>
                  </a>
                </h2>
                <div class="up-product-meta">￥--- / 1点</div>
                <div class="up-product-footer">
                  <a href="<?php the_permalink(); ?>" class="up-product-plus">＋</a>
                </div>
              </div>
            </article>
            <?php
          endwhile;
          ?>
        </div>

        <div class="up-cat-pagination">
          <?php the_posts_pagination(); ?>
        </div>

      <?php else : ?>
        <p class="up-empty">このカテゴリーには商品がありません。</p>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php get_footer(); ?>
