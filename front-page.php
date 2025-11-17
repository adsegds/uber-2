<?php
/**
 * フロントページ（Uber Eats 風トップ）
 */
get_header();
?>

<div class="up-shell">

  <!-- 青いヘッダー -->
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

  <!-- 2カラムレイアウト -->
  <div class="up-layout">

    <!-- 左：カテゴリーサイドバー -->
    <aside class="up-sidebar">
      <div class="up-sidebar-title">カテゴリー</div>
      <nav class="up-sidebar-nav">
        <a href="<?php echo esc_url( get_post_type_archive_link( 'item' ) ); ?>"
           class="up-sidebar-link up-sidebar-link--all">
          すべての商品
        </a>
        <?php
        $terms = get_terms(
          array(
            'taxonomy'   => 'item_category',
            'hide_empty' => false,
          )
        );
        if ( ! is_wp_error( $terms ) ) :
          foreach ( $terms as $term ) :
            ?>
            <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="up-sidebar-link">
              <?php echo esc_html( $term->name ); ?>
            </a>
            <?php
          endforeach;
        endif;
        ?>
      </nav>
    </aside>

    <!-- 右：メインコンテンツ -->
    <main class="up-main-area">

      <!-- 上部バナー（ダミー） -->
      <section class="up-banner-row">
        <div class="up-banner-card up-banner-card--orange">
          <div class="up-banner-title">Ponta IDで特典GET</div>
          <div class="up-banner-text">会員登録で限定クーポンが使えるようになります。</div>
        </div>
        <div class="up-banner-card up-banner-card--blue">
          <div class="up-banner-title">生活応援商品いろいろ</div>
          <div class="up-banner-text">からあげクン・弁当・飲料などをまとめて取り置き。</div>
        </div>
        <div class="up-banner-card up-banner-card--green">
          <div class="up-banner-title">深夜のご褒美セット</div>
          <div class="up-banner-text">お菓子＋ドリンクのテロ飯セットも準備中。</div>
        </div>
      </section>

      <?php
      // カテゴリー一覧（さっき取得できてなければもう一回）
      if ( ! isset( $terms ) || is_wp_error( $terms ) ) {
        $terms = get_terms(
          array(
            'taxonomy'   => 'item_category',
            'hide_empty' => false,
          )
        );
      }

      if ( ! is_wp_error( $terms ) && $terms ) :
        foreach ( $terms as $term ) :

          // 各カテゴリの商品を最大10件取得
          $items_query = new WP_Query(
            array(
              'post_type'      => 'item',
              'posts_per_page' => 10,
              'tax_query'      => array(
                array(
                  'taxonomy' => 'item_category',
                  'field'    => 'term_id',
                  'terms'    => $term->term_id,
                ),
              ),
            )
          );

          if ( $items_query->have_posts() ) :
            ?>
            <section class="up-section" id="section-<?php echo esc_attr( $term->slug ); ?>">
              <div class="up-section-header">
                <h2 class="up-section-title">
                  <?php echo esc_html( $term->name ); ?>
                </h2>
                <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="up-section-link">
                  すべて表示 →
                </a>
              </div>

              <div class="up-row-scroll">
                <?php
                while ( $items_query->have_posts() ) :
                  $items_query->the_post();
                  ?>
                  <article class="up-product-card">
                    <div class="up-product-image-wrap">
                      <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium', array( 'class' => 'up-product-image' ) ); ?>
                      <?php else : ?>
                        <div class="up-product-image up-product-image--placeholder">
                          画像なし
                        </div>
                      <?php endif; ?>
                      <div class="up-product-badge">取り置き可</div>
                    </div>

                    <div class="up-product-body">
                      <h3 class="up-product-name"><?php the_title(); ?></h3>
                      <div class="up-product-meta">￥--- / 1点</div>
                      <div class="up-product-footer">
                        <a href="<?php the_permalink(); ?>" class="up-product-plus">＋</a>
                      </div>
                    </div>
                  </article>
                  <?php
                endwhile;
                wp_reset_postdata();
                ?>
              </div>
            </section>
            <?php
          endif; // have posts
        endforeach;
      else :
        ?>
        <p class="up-empty">現在、商品カテゴリーが登録されていません。</p>
      <?php endif; ?>

    </main>
  </div><!-- /.up-layout -->

</div><!-- /.up-shell -->

<?php get_footer(); ?>
