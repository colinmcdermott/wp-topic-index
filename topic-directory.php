<?php
/*
Plugin Name: Topic Directory
Description: List all post tags & categories (merged) in an alphabetically ordered list.
Version: 1.0
Author: Colin McDermott
Author URI: https://www.searchcandy.uk
*/

function alphabetical_tags_and_categories() {

    // Get all tags
    $tags = get_tags( array('orderby' => 'name', 'order' => 'ASC') );

    // Get all categories
    $categories = get_categories( array('orderby' => 'name', 'order' => 'ASC') );

    // Check if tags or categories exist
    if ( !$tags && !$categories ) {
        return 'No tags or categories found.';
    }

    // Merge arrays
    $merged = array_merge($tags, $categories);

    // Sort array alphabetically
    usort($merged, function($a, $b) {
        return strcasecmp($a->name, $b->name);
    });

    // Group by first letter
    $grouped = array();
    foreach ($merged as $item) {
        $firstLetter = strtoupper(substr($item->name, 0, 1));
        if (!ctype_alpha($firstLetter)) {
            $firstLetter = '#';
        }
        if (!isset($grouped[$firstLetter])) {
            $grouped[$firstLetter] = array();
        }
        $grouped[$firstLetter][] = $item;
    }

    // Move #'s to end
    if (isset($grouped['#'])) {
        $numbers = $grouped['#'];
        unset($grouped['#']);
        $grouped['#'] = $numbers;
    }

    // Build index
    $index = '<div class="topic-index"><p>Jump to: ';
    foreach (array_keys($grouped) as $letter) {
        $index .= '<a href="#group-' . $letter . '">' . $letter . '</a> ';
    }
    $index .= '</p></div>';

    // Prepare itemList structured data
    $itemList = [];

    // Build output string
    $output = $index;
    $position = 1;
    foreach ($grouped as $letter => $items) {
        $output .= '<div class="topic-group">';
        $output .= '<h2 id="group-' . $letter . '">' . $letter . '</h2>';
        $output .= '<ul class="alphabetical-list">';
        foreach ($items as $item) {
            $output .= '<li><a href="' . get_term_link($item) . '">' . $item->name . '</a></li>';
            $itemList[] = [
                "@type" => "ListItem",
                "position" => $position,
                "item" => [
                    "@id" => get_term_link($item),
                    "name" => $item->name
                ]
            ];
            $position++;
        }
        $output .= '</ul></div>';
    }

    // Add itemList structured data
    $structuredData = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "itemListElement" => $itemList
    ];

    $output .= '<script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_SLASHES) . '</script>';

    return $output;
}

add_shortcode('alphabetical_tags_and_categories', 'alphabetical_tags_and_categories');

// Enqueue the CSS
function alphabetical_tags_and_categories_css() {
    wp_register_style( 'alphabetical_tags_and_categories_css', false );
    wp_enqueue_style( 'alphabetical_tags_and_categories_css' );
    $css = '.alphabetical-list { text-transform: capitalize; }';
    wp_add_inline_style( 'alphabetical_tags_and_categories_css', $css );
}

add_action( 'wp_enqueue_scripts', 'alphabetical_tags_and_categories_css' );
?>
