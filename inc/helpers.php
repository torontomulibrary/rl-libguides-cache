<?php
if ( !function_exists('array_flatten') ) {
  function array_flatten($items){
    if (!is_array($items)) {
      return [$items];
    }

    return array_reduce($items, function ($carry, $item) {
      return array_merge($carry, array_flatten($item));
    }, []);
  }
}
