<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * xmarticle module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @author          Mage Gregory (AKA Mage)
 */
use \Xmf\Request;

include_once __DIR__ . '/header.php';
$GLOBALS['xoopsOption']['template_main'] = 'xmarticle_search.tpl';
include_once XOOPS_ROOT_PATH.'/header.php';
include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

$xoTheme->addStylesheet( XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', 'n') . '/assets/css/styles.css', null );

// Get Permission to search
$permHelper->checkPermissionRedirect('xmarticle_other', 32, 'index.php', 2, _NOPERM);

// Get values
$search = Request::getString('search', '');
$reset = Request::getString('reset', '');
if ($reset == ''){
	$search_name = Request::getString('search_name', '');
	$search_reference = Request::getString('search_reference', '');
	$search_cat = Request::getInt('search_cat', 0);
} else {
	$search_name = '';
	$search_reference = '';
	$search_cat = 0;
}

// Get start pager
$start = Request::getInt('start', 0);

// Get Permission to view cat
$viewPermissionCat = XmarticleUtility::getPermissionCat('xmarticle_view');
$criteria = new CriteriaCompo();
$criteria->setSort('category_weight ASC, category_name');
$criteria->setOrder('ASC');
$criteria->add(new Criteria('category_status', 1));
if (!empty($viewPermissionCat)){
    $criteria->add(new Criteria('category_id', '(' . implode(',', $viewPermissionCat) . ')','IN'));
} else {
	redirect_header('index.php', 3, _MA_XMARTICLE_ERROR_NOACESSCATEGORY);
}
$category_arr = $categoryHandler->getall($criteria);

$form = new XoopsThemeForm(_MA_XMARTICLE_SEARCHFORM, 'form', 'search.php', 'post', true);
// title
$form->addElement(new XoopsFormText(_MA_XMARTICLE_ARTICLE_NAME, 'search_name', 50, 255, $search_name));
//reference
$form->addElement(new XoopsFormText(_MA_XMARTICLE_ARTICLE_REFERENCE, 'search_reference', 50, 255, $search_reference));
//cat
$field_cat = new XoopsFormSelect(_MA_XMARTICLE_ARTICLE_CATEGORY, 'search_cat', $search_cat);
$field_cat->addOption(0, _ALL);
foreach (array_keys($category_arr) as $i) {
	$field_cat->addOption($category_arr[$i]->getVar('category_id'), $category_arr[$i]->getVar('category_name'));
}
$form->addElement($field_cat);
// search
$button = new XoopsFormElementTray('');
$button->addElement(new XoopsFormButton('', 'search', _SEARCH, 'submit'));
$button->addElement(new XoopsFormButton('', 'reset', _RESET, 'submit'));
//$form->addElement(new XoopsFormButtonTray('', _SEARCH, 'submit', 'search.php', true));
$form->addElement($button);

$xoopsTpl->assign('form', $form->render());

if ($search != ''){
	$arguments = 'search_cat=' . $search_cat . '&amp;';
	// Criteria
	$criteria = new CriteriaCompo();
	if ($search_name != '') {
		$criteria->add(new Criteria('article_name', '%' . $search_name . '%', 'LIKE'));
		$arguments .= 'search_name=' . $search_name . '&amp;';
	}
	if ($search_reference != '') {
		$criteria->add(new Criteria('article_reference', '%' . $search_reference . '%', 'LIKE'));
		$arguments .= 'search_reference=' . $search_reference . '&amp;';
	}
	$criteria->setSort('article_name');
	$criteria->setOrder('ASC');
	$criteria->setStart($start);
	$criteria->setLimit($nb_limit);
	if ($search_cat != 0){
		$criteria->add(new Criteria('article_cid', $search_cat));
	}
	$criteria->add(new Criteria('article_status', 1));   
	$articleHandler->table_link = $articleHandler->db->prefix("xmarticle_category");
	$articleHandler->field_link = "category_id";
	$articleHandler->field_object = "article_cid";
	$article_arr = $articleHandler->getByLink($criteria);
	$article_count = $articleHandler->getCount($criteria);
	if ($article_count > 0) {
		foreach (array_keys($article_arr) as $i) {
			$article_id                 = $article_arr[$i]->getVar('article_id');
			$article['id']              = $article_id;
			$article['cid']             = $article_arr[$i]->getVar('article_cid');
			$article['name']            = $article_arr[$i]->getVar('article_name');
			$article['reference']       = $article_arr[$i]->getVar('article_reference');
			$article['description']     = $article_arr[$i]->getVar('article_description', 'show');
			$article['date']            = formatTimestamp($article_arr[$i]->getVar('article_date'), 's');
			$article['author']          = XoopsUser::getUnameFromId($article_arr[$i]->getVar('article_userid'));
			$article_img                = $article_arr[$i]->getVar('article_logo') ?: 'blank.gif';
			$article['logo']            = $url_logo_article .  $article_img;
			$xoopsTpl->append('article', $article);
			unset($article);
		}
		// Display Page Navigation
		if ($article_count > $nb_limit) {
			$nav = new XoopsPageNav($article_count, $nb_limit, $start, 'start', 'search=Y&amp;' . $arguments);
			$xoopsTpl->assign('nav_menu', $nav->renderNav(4));
		}
	}
}

//SEO
// pagetitle
//$xoopsTpl->assign('xoops_pagetitle', $xoopsModule->name());
//keywords  
//$xoTheme->addMeta('meta', 'keywords', $keywords);
include XOOPS_ROOT_PATH.'/footer.php';