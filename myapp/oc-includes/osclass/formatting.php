<?php
/*
 * Copyright 2014 Osclass
 * Copyright 2021 Osclass by OsclassPoint.com
 *
 * Osclass maintained & developed by OsclassPoint.com
 * You may not use this file except in compliance with the License.
 * You may download copy of Osclass at
 *
 *     https://osclass-classifieds.com/download
 *
 * Do not edit or add to this file if you wish to upgrade Osclass to newer
 * versions in the future. Software is distributed on an "AS IS" basis, without
 * warranties or conditions of any kind, either express or implied. Do not remove
 * this NOTICE section as it contains license information and copyrights.
 */


/**
 * Escape all the values of an array.
 *
 * @param array $array Array used to apply addslashes.
 * @return array $array after apply addslashes.
 */
function add_slashes_extended($array) {
  foreach ((array) $array as $k => $v) {
    if(is_array($v)) {
      $array[$k] = add_slashes_extended($v);
    } else {
      $array[$k] = addslashes($v);
    }
  }

  return $array;
}


/**
 * @param $string
 *
 * @return mixed|null|string|string[]
 */
function osc_sanitizeString($string) {
  if ($string === '' || $string === null) {
    return '';
  }
  
  $string = ($string !== NULL ? strip_tags($string) : '');
  $string = preg_replace('/%([a-fA-F0-9][a-fA-F0-9])/', '--$1--', $string);
  $string = str_replace('%', '', $string);
  $string = preg_replace('/--([a-fA-F0-9][a-fA-F0-9])--/', '%$1', $string);

  $string = remove_accents($string);

  //$string = strtolower($string);
  // @TODO  retrieve $arr_stop_words from Locale user custom list. as editable in /oc-admin/index.php?page=languages
  //    and do a 
  //    str_replace($arr_stop_words, '', $string);
  $string = preg_replace('/&.+?;/', '', $string);
  $string = str_replace(array('.','\'','--'), '-', $string);
  $string = preg_replace('/\s+/', '-', $string);
  $string = preg_replace('|[\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Po}\p{S}\p{Z}\p{C}\p{No}]+|u', '', $string);

  if(is_utf8($string)) {
    if(!defined('OSC_FORCE_DISABLE_URL_ENCODING') || (defined('OSC_FORCE_DISABLE_URL_ENCODING') && OSC_FORCE_DISABLE_URL_ENCODING != true)) {
      $string = urlencode($string);
    }
    
    // mdash & ndash
    $string = str_replace(array('%e2%80%93', '%e2%80%94'), '-', strtolower($string));
  }

  $string = preg_replace('/-+/', '-', $string);
  $string = trim($string, '-');

  return $string;
}


/**
 * @param $string
 *
 * @return mixed|null|string|string[]
 */
function remove_accents($string) {
  if ($string === '' || $string === null) {
    return '';
  }
  
  if (!preg_match('/[\x80-\xff]/' , $string)) {
    return $string;
  }
  
  if (is_utf8($string)) {
    $chars = array(
      // Decompositions for Latin-1 Supplement
      chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
      chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
      chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
      chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
      chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
      chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
      chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
      chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
      chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
      chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
      chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
      chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
      chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
      chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
      chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
      chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
      chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
      chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
      chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
      chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
      chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
      chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
      chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
      chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
      chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
      chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
      chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
      chr(195).chr(191) => 'y',
      // Decompositions for Latin Extended-A
      chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
      chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
      chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
      chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
      chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
      chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
      chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
      chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
      chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
      chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
      chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
      chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
      chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
      chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
      chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
      chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
      chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
      chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
      chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
      chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
      chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
      chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
      chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
      chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
      chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
      chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
      chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
      chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
      chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
      chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
      chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
      chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
      chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
      chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
      chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
      chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
      chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
      chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
      chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
      chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
      chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
      chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
      chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
      chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
      chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
      chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
      chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
      chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
      chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
      chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
      chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
      chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
      chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
      chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
      chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
      chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
      chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
      chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
      chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
      chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
      chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
      chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
      chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
      chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
      // Euro Sign
      chr(226).chr(130).chr(172) => 'E',
      // GBP (Pound) Sign
      chr(194).chr(163) => ''
   );

    
    // update 812, Turkish to Latin
    $trk_from = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ö', 'Ç');
    $trk_to   = array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 'o', 'c');

    $string = str_replace($trk_from, $trk_to, $string);
    
    
    // update 812, Greek to Latin
    $expressions = array(
      '/[αάΑΆ]/u'   => 'a',
      '/[βΒ]/u'     => 'v',
      '/[γΓ]/u'     => 'g',
      '/[δΔ]/u'     => 'd',
      '/[εέΕΈ]/u'   => 'e',
      '/[ζΖ]/u'     => 'z',
      '/[ηήΗΉ]/u'   => 'i',
      '/[θΘ]/u'     => 'th',
      '/[ιίϊΙΊΪ]/u' => 'i',
      '/[κΚ]/u'     => 'k',
      '/[λΛ]/u'     => 'l',
      '/[μΜ]/u'     => 'm',
      '/[νΝ]/u'     => 'n',
      '/[ξΞ]/u'     => 'x',
      '/[οόΟΌ]/u'   => 'o',
      '/[πΠ]/u'     => 'p',
      '/[ρΡ]/u'     => 'r',
      '/[σςΣ]/u'    => 's',
      '/[τΤ]/u'     => 't',
      '/[υύϋΥΎΫ]/u' => 'y',
      '/[φΦ]/iu'    => 'f',
      '/[χΧ]/u'     => 'ch',
      '/[ψΨ]/u'     => 'ps',
      '/[ωώ]/iu'    => 'o',
      '/[αΑ][ιίΙΊ]/u'                             => 'e',
      '/[οΟΕε][ιίΙΊ]/u'                           => 'i',
      '/[αΑ][υύΥΎ]([θΘκΚξΞπΠσςΣτTφΡχΧψΨ]|\s|$)/u' => 'af$1',
      '/[αΑ][υύΥΎ]/u'                             => 'av',
      '/[εΕ][υύΥΎ]([θΘκΚξΞπΠσςΣτTφΡχΧψΨ]|\s|$)/u' => 'ef$1',
      '/[εΕ][υύΥΎ]/u'                             => 'ev',
      '/[οΟ][υύΥΎ]/u'                             => 'ou',
      '/(^|\s)[μΜ][πΠ]/u'                         => '$1b',
      '/[μΜ][πΠ](\s|$)/u'                         => 'b$1',
      '/[μΜ][πΠ]/u'                               => 'b',
      '/[νΝ][τΤ]/u'                               => 'nt',
      '/[τΤ][σΣ]/u'                               => 'ts',
      '/[τΤ][ζΖ]/u'                               => 'tz',
      '/[γΓ][γΓ]/u'                               => 'ng',
      '/[γΓ][κΚ]/u'                               => 'gk',
      '/[ηΗ][υΥ]([θΘκΚξΞπΠσςΣτTφΡχΧψΨ]|\s|$)/u'   => 'if$1',
      '/[ηΗ][υΥ]/u'                               => 'iu',
    );

    $string = preg_replace(array_keys($expressions), array_values($expressions), $string);


    // update 420, greek to latin
    //$cyr = array('α','β','γ','δ','ε','ζ','η','θ','ι','κ','λ','μ','ν','ξ','ο','п','ρ','σ','τ','υ','φ','χ','ψ','ω','Α','Β','Γ','Δ','Ε','Ζ','Η','Θ','Ι','Κ','Λ','Μ','Ν','Ξ','Ο','Π','Ρ','Σ','Τ','Υ','Φ','Χ','Ψ','Ω','ί','ό','ώ','ά','ή','έ','€');
    //$lat = array('a','b','g','d','e','z','i','th','i','k','l','m','n','x','o','p','r','s','t','y','f' ,'x' ,'ps' ,'w','A','B','G','D','E','Z','I','TH','I','K','L','M','N','X','O','P','R','S','T','Y','F' ,'X' ,'Ps' ,'W' ,'i' ,'ο' ,'w', 'a', 'h', 'e', 'euro');
    //$string = str_replace($cyr, $lat, $string); 
    
    // update 440, cyrilic to latin
    // $cyr = array('а','б','в','г','д','ѓ','е','ж','з','ѕ','и','ј','к','л','љ','м','н','њ','о','п','р','с','т','ќ','у','ф','х','ц','ч','џ','ш','А','Б','В','Г','Д','Ѓ','Е','Ж','З','Ѕ','И','Ј','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','Ќ','У','Ф','Х','Ц','Ч','Џ','Ш','€');
    // $lat = array('a','b','v','g','d','gj','e','zh','z','dz','i','j','k','l','lj','m','n','nj','o','p','r','s','t','kj','u','f','h','c','ch','dz','sh','A','B','V','G','D','GJ','E','ZH','Z','DZ','I','J','K','L' ,'LJ' ,'M' ,'N' ,'NJ' ,'O' ,'P', 'R', 'S', 'T','KJ','U','F','H','C','CH','DZ','SH','EUR');

    // update 450, cyrilic to latin extended
    $cyr = array('А','а','Б','б','В','в','Г','г','Ґ','ґ','Д','д','Ђ','ђ','Е','е','Ё','ё','Ж','ж','З','з','З́','з́','И','и','Й','й','Ѝ','ѝ','І','і','Ї','ї','Ј','ј','К','к','Л','л','Љ','љ','М','м','Н','н','Њ','њ','О','о','Ō','ō','П','п','Р','р','С','с','С́','с́','Т','т','Ћ','ћ','Ќ','ќ','У','у','Ӯ','ӯ','Ў','ў','Ф','ф','Х','х','Ц','ц','Ч','ч','Џ','џ','Ш','ш','Щ','щ','Ъ','ъ','Ы','ы','Ь','ь','Э','э','Ю','ю','Я','я','є');
    $lat = array('A','a','B','b','V','v','G','g','Gj','gj','D','d','Dz','dz','E','e','Jo','jo','Zh','zh','Z','z','Z','z','I','i','J','j','J','j','J','j','J','j','J','j','K','k','L','l','Lj','lj','M','m','N','n','Nj','nj','O','o','O','o','P','p','R','r','S','s','S','s','T','t','T','t','Kj','kj','U','u','U','u','U','u','F','f','H','h','C','c','Ch','ch','Dzh','dzh','Sh','sh','Shh','shh','','','Y','y','','','Je','je','Ju','ju','Ja','ja','je');

    $string = str_replace($cyr, $lat, $string);
    $string = strtr($string, $chars);
    

    // update 812
    // $string = preg_replace("/[^A-Za-z0-9'_\-\.]/", '-', $string);
    // $string = preg_replace('/\-+/', '-', $string);
    // $string = preg_replace('/^-+/', '', $string);
    // $string = preg_replace('/-+$/', '', $string);

  } else {

    // Assume ISO-8859-1 if not UTF-8
    $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
      .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
      .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
      .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
      .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
      .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
      .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
      .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
      .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
      .chr(252).chr(253).chr(255);

    $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

    $string = strtr($string, $chars['in'], $chars['out']);
    $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
    $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
    $string = str_replace($double_chars['in'], $double_chars['out'], $string);
  }

  $string = strtolower(htmlentities($string));
  $string = preg_replace('#&([a-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '$1', $string);
  $string = preg_replace('#&([a-z]{2})(?:lig);#', '$1', $string);
  $string = preg_replace('#&[^;]+;#', '', $string);

  // Remove Vietnamese Accents
  $string = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $string);
  $string = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $string);
  $string = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $string);
  $string = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $string);
  $string = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $string);
  $string = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $string);
  $string = preg_replace("/(đ)/", 'd', $string);

  $string = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $string);
  $string = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $string);
  $string = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $string);
  $string = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $string);
  $string = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $string);
  $string = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $string);
  $string = preg_replace("/(Đ)/", 'D', $string);

  return $string;
}


/**
 * @param $string
 *
 * @return false|int
 */
function is_utf8($string) {
  return preg_match('%^(?:
      [\x09\x0A\x0D\x20-\x7E]      # ASCII
    | [\xC2-\xDF][\x80-\xBF]       # non-overlong 2-byte
    |  \xE0[\xA0-\xBF][\x80-\xBF]    # excluding overlongs
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
    |  \xED[\x80-\x9F][\x80-\xBF]    # excluding surrogates
    |  \xF0[\x90-\xBF][\x80-\xBF]{2}   # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3}      # planes 4-15
    |  \xF4[\x80-\x8F][\x80-\xBF]{2}   # plane 16
  )*$%xs', $string);
}