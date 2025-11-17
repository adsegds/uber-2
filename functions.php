<?php
/**
 * テーマの基本設定
 */

// アイキャッチ
add_theme_support( 'post-thumbnails' );

// style.css を読み込み
function up_enqueue_assets() {
    wp_enqueue_style( 'up-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'up_enqueue_assets' );


// ▼ 商品用カスタム投稿タイプ item
function up_register_item_cpt() {
    $labels = array(
        'name'               => '商品',
        'singular_name'      => '商品',
        'menu_name'          => '商品',
        'name_admin_bar'     => '商品',
        'add_new'            => '新規追加',
        'add_new_item'       => '商品を追加',
        'new_item'           => '新規商品',
        'edit_item'          => '商品を編集',
        'view_item'          => '商品を表示',
        'all_items'          => 'すべての商品',
        'search_items'       => '商品を検索',
        'not_found'          => '商品が見つかりません',
        'not_found_in_trash' => 'ゴミ箱に商品はありません',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => true,
        'menu_position'      => 5,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'items' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'item', $args );
}
add_action( 'init', 'up_register_item_cpt' );


// ▼ 商品カテゴリー item_category
function up_register_item_category_tax() {
    $labels = array(
        'name'          => '商品カテゴリー',
        'singular_name' => '商品カテゴリー',
        'search_items'  => 'カテゴリーを検索',
        'all_items'     => 'すべてのカテゴリー',
        'edit_item'     => 'カテゴリーを編集',
        'update_item'   => 'カテゴリーを更新',
        'add_new_item'  => '新規カテゴリーを追加',
        'new_item_name' => '新しいカテゴリー名',
        'menu_name'     => '商品カテゴリー',
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'item-category' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'item_category', array( 'item' ), $args );
}
add_action( 'init', 'up_register_item_category_tax' );








// item 投稿タイプの REST API を完全に無効化
add_filter( 'register_post_type_args', function( $args, $post_type ) {
    if ( $post_type === 'item' ) {
        $args['show_in_rest'] = false;     // RESTを完全に止める
        $args['supports'] = array( 'title', 'editor', 'thumbnail' ); // クラシック用
    }
    return $args;
}, 20, 2 );

