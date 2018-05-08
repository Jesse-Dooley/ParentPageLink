<?php
/*
 *
 * @name ParentPageLink
 * @author Jesse B. Dooley
 * @date September 25, 2016
 * @description
 * On new page edit add this boilerplate at page bottom that inserts
 * a page-link pointing to the parent page.
 * 
 * @version 0.057
 * Changes: Fixes bug with Categories
 *          Fixes bug with Talk Pages
 *          Removed '_' from article title
 *          Fixed bug with 'Main Page'
 *          Fixed error message
 *          Fixed blank article detection error in 1.27.1
 *          Fixed getContent() bug in 1.30.0
 *
 * Example:
 * \n
 * \n
 * =Internal Links=
 * <i>Parent Article:</i> [[<page with the link being used>]]<br />
 *
 */
 
if (!defined('MEDIAWIKI')) {
        echo "To install the ParentPageLink extension, put the following line in LocalSettings.php:require_once( {$IP}extensions\ParentPageLink.php );";
        exit( 1 );
}

$wgHooks['EditPage::showEditForm:initial'][] = array('ParentPageLink');
 
$wgExtensionCredits['other'][] = array(
    'name' => 'ParentPageLink',
    'author' => 'Jesse B. Dooley',
    'url' => 'http://www.mediawiki.org/wiki/Extension:ParentPageLink',
    'description' => 'Adds a Parent Article Link',
    'descriptionmsg' => 'Adds a Parent Article Link as boilerplate',
    'version' => '0.057',
);

function parentpagelink($editpage) {
    // EditFormPreloadText
    global $wgOut;
    global $wgEnableAPI;
    global $wgEnableWriteAPI;
    $wgOut->enableClientCache(false);
    $wgEnableAPI = true;
    
    # A blank article has no content
#     if ( is_null( $editpage->mArticle->getContent() ) ) {
    if ( strlen( $editpage->mArticle->mContent ) == 0 ) {
		echo "is null";
        $parentID = "";
        $parentName = "";
        $category_name = "";
        $title_name  = "";
        $parent_namespace = "";
        $parent_name = ""; # Displayed in the link

        $title_object = $editpage->getTitle();

        # Test for Talk Page and skip
        if( $title_object->isTalkPage() ) {
            return true;
        }

        # get the current article title in text form
        $title_name = $title_object->mTextform;
        
        # Parse title_name for category
        $pieces = explode(':', $title_name);

        # test if the article has a category
        if ( count( $pieces ) > 1 ) {
            $category_name = $pieces[0];
            $title_name = $pieces[1];
        }

        $dbr = wfGetDB( DB_MASTER );
        # select to grab parent id
        $res = $dbr->select(
                'pagelinks',
                array('pl_from'),
                array( 'pl_title' => $title_object->getDBkey()),
                __METHOD__,
                array( 'ORDER BY' => 'pl_from ASC' )
        );

        # get the $parentID
        foreach( $res as $row ) {
            $parentID = $row->pl_from;
            break;
        }

        # get the parent title
        $res = $dbr->select(
                'page',
                array( 'page_title', 'page_namespace' ),
                array('page_id' => $parentID),
                __METHOD__
        );
    
        foreach( $res as $row ) {
            $parentName = $row->page_title;
            $parent_namespace = MWNamespace::getCanonicalName( $row->page_namespace );
            break;
        }

        $parentSlice = explode("/", $parentName);

        # Check for Category
        if( $parent_namespace ) {
        # Add Namespace
            $parent_name = $parent_namespace . ":" . $parentName . " | " . $parentName;
        } elseif ( count( $parentSlice ) > 1 ){    # Check for subpage
            $parent_name = $parentName . " | " . end( $parentSlice );
        }  else {
            $parent_name = $parentName;
        }
        
        # Replace _ for " "
        $parent_name = str_replace ("_", " ", $parent_name);
        
        # Add the parent link, modify the string as needed
        $editpage->textbox1= "\n\n=Internal Links=\n<i>Parent Article:</i> [[" . $parent_name ."]]<br />";
        $editpage->textbox2=$editpage->textbox1;
    } // end if

    return true;
} // function parentpagelink($editpage) 
?>