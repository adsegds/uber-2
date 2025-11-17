<?php
/**
 * 注文確認（ダミー）ページ
 * 固定ページ「注文確認」をこのテンプレートで使うイメージ
 */
get_header();
?>

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
      ORDER
    </div>
  </header>

  <main class="up-main">
    <article class="up-card">
      <div class="up-card-body">
        <h1 class="up-card-title">ご注文ありがとうございます（ダミー）</h1>
        <div class="up-card-desc">
          <p>ここに「注文番号」や「受け取り時間」などの案内文を入れていく想定です。</p>
          <p>実際の連携ロジックは、あとで STORES 側と合わせて考えていけばOK。</p>
        </div>
      </div>
    </article>
  </main>
</div>

<?php get_footer(); ?>
