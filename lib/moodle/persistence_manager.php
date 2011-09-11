<?php

/**
 * This file contains wiki persistance manager interface implementation
 * for Moodle environment.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: persistence_manager.php,v 1.56 2008/05/26 12:06:43 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */

global $CFG;

require_once ($CFG->dirroot.'/mod/wiki/lib/persistence_manager_interface.php');
require_once ($CFG->dirroot.'/mod/wiki/db/moodle/wiki_persistor.php');
require_once ($CFG->dirroot.'/mod/wiki/lib/wiki_synonym.class.php');
require_once ($CFG->dirroot.'/mod/wiki/lib/wiki_pageid.class.php');

class persistence_manager extends persistence_manager_interface{

	var $wikipersistor;

	function persistence_manager(){

		$this->wikipersistor = new wiki_persistor();
	}

	/**
     *  Saves the wiki in the database.
     *
     *  @param Wiki $wiki
     */
	function save_wiki($wiki){
		return $this->wikipersistor->save_wiki($wiki);
	}

	/**
     *  Updates the wiki data in the database.
     *
     *  @param wiki $wiki
     *
     */
	function update_wiki($wiki){
		return $this->wikipersistor->update_wiki($wiki);
	}

    /**
     * Delete a wiki and all record that depend on it.
     *
     * @param int $wikiid
     *
     * @return bool
     */
    function delete_wiki($wikiid) {
        $this->wikipersistor->delete_wiki($wikiid);
    }

    /**
     * Sets editables on all pages of the wiki.
     *
     * @param int $wikiid
     * @param int $editable
     */
    function pages_set_editable($wikiid, $editable) {
        $this->wikipersistor($wikiid, $editable);
    }

	/**
     *  Saves the wikipage in the database. Updates the author, user_id, last_modified and owner.
     *  It also sets the version as one more than the las version of the wikipage.
     *  Returns the wikipage id if correct or false if failed.
     *
     *  @param Wikipage $wikipage
	 *  @return int
     */
	function save_wiki_page($wikipage){
		global $USER,$WS;
		
		$version = $this->wikipersistor->
						  get_last_wiki_page_version($wikipage->page_name(), $wikipage->dfwiki(),
													 $wikipage->group_id(), $wikipage->owner_id());
		$wikipage->set_version($version+1);
		$wikipage->set_author($USER->username);
        $wikipage->set_user_id($USER->id);
        $wikipage->set_owner_id($WS->member->id);
        $wikipage->set_last_modified(time());

		$record=$wikipage->wiki_page_to_record();

		return $this->wikipersistor->save_wiki_page($record);
	}

	/**
     *  Returns the wiki with the id given, or false if it does not exist.
     *  @return Wiki
     */

	function get_wiki_by_id($wikiid){
		if (!isset($this->wikis)) $this->wikis = array();
		if (isset($this->wikis[$wikiid])) return $this->wikis[$wikiid];
		if ($record = $this->wikipersistor->get_wiki_by_id($wikiid)){
			$this->wikis[$wikiid] =  new Wiki(RECORD, $record);
			return $this->wikis[$wikiid];
		}
		return false;

	}

	/**
     *  Returns the wikipage with the id given, or false if it does not exist.
     *
     *  @return Wikipage
     */

	function get_wiki_page_by_id($wikipageid){
		if ($record = $this->wikipersistor->get_wiki_page_by_id($wikipageid)){
			return new wiki_page(PAGERECORD, $record);
		}
		return null;
	}
	
	/**
	 * returns a wikipage with the pagename given or false if does not exists
	 * @param wiki $wiki: wiki object
	 * @param String $pagename
	 * @param int $version=false (last version if false)
	 * @param int $groupid=0
	 * @param int $owner=0
	 * @return wikipage or null
	 */
	function get_wiki_page_by_pagename ($wiki,$pagename,$version=false,$groupid=0,$owner=0) {
		if ($record = $this->wikipersistor->get_wiki_page_by_pagename ($wiki->id,$pagename,$version,$groupid,$owner)){
			return new wiki_page(PAGERECORD, $record);
		}
		return null;
	}

    /**
     * Returns the page or false if it doesn't exist.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  wiki_page|false
     */
    function get_wiki_page_by_pageid($pageid) {
        $record = $this->wikipersistor->get_wiki_page_by_pagename($pageid->wikiid,
            $pageid->name, $pageid->version, $pageid->groupid, $pageid->ownerid);
        if ($record) {
            return new wiki_page(PAGERECORD, $record);
        } else {
            return null;
        }
    }

    /**
     * Returns true if page exists, false otherwise.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  bool
     */
    function page_exists($pageid) {
        return $this->wikipersistor->page_exists($pageid->wikiid, $pageid->name,
            $pageid->version, $pageid->groupid, $pageid->ownerid);
    }

	/**
     *  Get array of wikis by course id or false if course does not exist.
     *
     *  @param String $courseid
     *  @return Array of Wiki
     */
	function get_wikis_by_course($courseid){

		if ($records =$this->wikipersistor->get_wikis_by_course($courseid)){
            $wikis = array();
            foreach ($records as $record) {
				$wikis[] = new Wiki(RECORD, $record);
            }
            return $wikis;
        }
		return false;
	}

	/**
     *  It returns a list with the wiki pages contained in the wiki given, or false if it does not exist.
     *
     *  @param  String $wikiid
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of Wikipage
     *
     */
	function get_wiki_pages_by_wiki($wiki, $groupid, $ownerid){
		if ($records = $this->wikipersistor->get_wiki_pages_by_wiki($wiki->id, $groupid, $ownerid)){
			$wikipages = array();
            foreach ($records as $record) {
				$wikipages[] = new wiki_page(PAGERECORD, $record);
            }
            return $wikipages;
        }
		return false;
	}


	/**
     *  It returns a list with the wiki pages contained
     *  in the wiki given which are from the group "$groupid".
     *
     *  @param  String $wikiid
     *  @param  Integer $groupid
     *  @return Array of wiki_page
     *
     */
	function get_wiki_pages_by_group($wikiid, $groupid){
		if ($records = $this->wikipersistor->get_wiki_pages_by_group($wikiid,$groupid)){
            $wikipages = array();
            foreach ($records as $record) {
				$wikipages[] = new wiki_page(PAGERECORD, $record);
            }
            return $wikipages;
        }
		return false;
	}

	/**
     *  It returns a list with the historic wikipages of a wikipage given, or false if it does not exist.
     *
     *  @param  Wikipage $wikipage
     *  @return Array of Wikipage
     *
     */
	function get_wiki_page_historic($wikipage){
		$records = $this->wikipersistor->get_wiki_page_historic
					($wikipage->page_name(),$wikipage->dfwiki(),$wikipage->group_id(),$wikipage->owner_id());
		if ($records){
			$wikipages = array();
            foreach ($records as $record) {
				$wikipages[] = new wiki_page(PAGERECORD, $record);
            }
            return $wikipages;
        }
		return false;
	}

	/**
     *  It returns a list of the users who are owners of a wikipage contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  wiki $wiki
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array
     *
     */
	function get_wiki_page_owners_of_wiki($wiki, $groupid, $ownerid){
		if ($records = $this->wikipersistor->get_wiki_page_owners_of_wiki(
												$wiki->id, $groupid, $ownerid)){
            $authors = array();
            foreach ($records as $record) {
				$authors[] = $record->firstname.' '.$record->lastname;
            }
            return $authors;
        }
		return false;
	}

	/**
     *  It returns a list of id the users who are owners of a wikipage contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  int $wikiid
     *  @return Array
     *
     */
	function get_wiki_page_owners_ids_of_wiki($wikiid){
		if ($records = $this->wikipersistor->get_wiki_page_owners_ids_of_wiki($wikiid)){
            $authors_id = array();
            foreach ($records as $record) {
				$authors_id[] = $record->id;
            }
            return $authors_id;
        }
		return false;
	}

	/**
     *  It returns a list with the names of the wikipages contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  Object $wiki
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of stdClass
     *
     */
	function get_wiki_page_names_of_wiki($wiki, $groupid, $ownerid=0){
		if ($records = $this->wikipersistor->get_wiki_page_names_of_wiki($wiki->id, $groupid, $ownerid)){
            $names = array();
            foreach ($records as $record) {
				$names[] = $record->pagename;
            }
            return $names;
        }
		return false;
	}
	
    /**
     * Array of pages of the user, ordered by last modified.
     *
     * @param string $username
     * @param int $wikiid
     *
     * return array of pages
     */
    function get_wiki_pages_user_outline($username, $wikiid) {
        return $this->wikipersistor->get_wiki_pages_user_outline($username, $wikiid);
    }

	/**
     *  It returns highest number of version of the wikipage given.
     *
     *  @param  Object $wikipage
     *  @param  integer $groupid
     *  @param  integer $memberid
     *  
     *  @return integer
     *
     */
	function get_last_wiki_page_version($wikipage,$groupid,$ownerid){
		if ($record = $this->wikipersistor->get_last_wiki_page_version
						($wikipage->pagename, $wikipage->dfwiki, $groupid, $ownerid)){
			return $record;
        }
		return false;
		
	}
	
	/**
     *  It returns all wikipages ordered by numeber of hits.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass
     *
     */	
	function get_most_viewed_pages($wikiid=false){
		if ($record = $this->wikipersistor->get_most_viewed_pages($wikiid)){
			return $record;
        }
		return array();
	}
	
	    /**
     * return an array of all visibles pages from the
     * first page in the current dfwiki
     * @return array of Strings
     */
    function get_visible_pages (){
		return $this->wikipersistor->get_visible_pages();
    }

    /**
     * return an array of all visibles pages from a pagename
     * @param String $page: startingpagename
     * @return array of Strings
     */
    function get_visible_pages_from_page ($page){
  		  	return $this->wikipersistor->get_visible_pages_from_page($page);
    }
	
	
	/**
     *  It returns all wikipages ordered by date of creation.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass
     *
     */
	function get_wiki_newest_pages($wikiid){
		if($record = $this->wikipersistor->get_wiki_newest_pages($wikiid)){
			return $record;
		}
		return array();
	}
	
	/**
     *  It returns all wikipages ordered by number of version.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass
     *
     */
	function get_wiki_most_uptodate_pages($wikiid){
		if($record = $this->wikipersistor->get_wiki_most_uptodate_pages($wikiid)){
			return $record;
		}
		return array();		
	}
	
	function get_wiki_orphaned_pages($num=-1){
		if ($record = $this->wikipersistor->get_wiki_orphaned_pages($num)){
			return $record;
        }
		return array();
	}
	
	function get_wiki_synonyms($name=false){
		if ($record = $this->wikipersistor->get_wiki_synonyms($name)){
			return $record;
        }
		return array();
	}	
	
	/**
     *  returns an array with the wanted pages
     * 
     *  @param  Array $pages: Pages of wiki
     */	
	function get_wiki_wanted_pages($pages){
		
		global $WS;
    	$res = array();
    	foreach ($pages as $page){
    		if ($pageinfo = wiki_page_last_version($page)){
    			$pagelinks = wiki_internal_link_to_array ($pageinfo->refs);
    			foreach ($pagelinks as $pagelink){
    				$pagelink = wiki_get_real_pagename ($pagelink);
    				if (!in_array($pagelink,$pages) && !in_array($pagelink,$res)){
    					$res[] = $pagelink;
    				}
    			}
    		}
    	}
    	return $res;
	}
	
	/**
	 * this function return the real name if it's a synonymous,
	 * or the same pagename otherwise.
	 * 
	 * @param   string  $name       name of the page.
	 * @param   int     $id         dfwiki instance id, current dfwiki default
     * @param   int     $onwerid
     * @param   int     $groupid
	 * @return  string
	 */
	function wiki_get_real_pagename ($name, $id=false, $groupid=null, $ownerid=null) {
		return $this->wikipersistor->wiki_get_real_pagename ($name, $id=false, $groupid, $ownerid);
	}
	
	/**
     *  Changes the wiki page name by the $pagename given. Aplies the changes to all the
     *  wiki page historic.
     *
     *  @param  String    $pagename
     *  @param  Wiki_page $wikipage
     *  
     */	
	function change_wiki_page_name($pagename, $wikipage){
		
		return $this->wikipersistor->change_wiki_page_name($pagename, $wikipage->page_name(),
										$wikipage->dfwiki(), $wikipage->group_id(), $wikipage->owner_id());
	}
	
	/**
     *  Forbides the edition of the wikipage, updating all the historic to avoid edition too.
     *
     *  @param  Wiki_page $wikipage
     *  
     */	
	function disable_wiki_page_edition($wikipage){

		return $this->wikipersistor->disable_wiki_page_edition($wikipage->page_name(),
										$wikipage->dfwiki(), $wikipage->group_id(), $wikipage->owner_id());

	}
	

		
	/**
     *  Creates a XML file with all the data of the wiki in the folder given.
     *
     *  @param  Wiki 	$wiki
     *  @param  String	$folder
     *  
     */	
	function export_wiki_to_XML($wiki, $folder = 'exportedfiles'){

		return $this->wikipersistor->export_wiki_to_XML($wiki, $folder);
	}
	
	/**
     *  Obtains the discussion pages of a wikipage.
     *
     *  @param  Wikipage 	$wikipage
     *  @return Array of Wikipage
     *  
     */	
	function get_discussion_page_of_wiki_page($wikipage){
		
		return $this->wikipersistor->get_discussion_page_of_wiki_page($wikipage->page_name(), $wikipage->dfwiki(),
														$wikipage->group_id(), $wikipage->owner_id());
	}
	
	/**
     *  It returns the users ordered by the number of wikipages saved.
     *
     *  @param  wiki $wiki
     *  
     *  @return Array of String
     *
     */
	function get_activest_users($wiki, $num = -1){
		
		//$this->wikipersistor->get_activest_users($wiki->id);
		
	    //get the page list of active users
		$users = $this->active_users($wiki);

		//built list
		$active = array();
		foreach ($users as $user){
			if ($num!=0){
				$active[$user] = $this->user_num_activity($wiki,$user);
				$num--;
			}
		}

		//order list.
		arsort($active);
		//built result
		foreach ($active as $user=>$activity){
			$res[] = $user;
		}
		return $res;
	
	}
	
	
	/**
	 * Return an array with all active users
	 * 
	 * @param Wiki	$wiki
	 * @param int $groupid=0
	 * 
	 * @return Integer 
	 */
    function active_users($wiki, $groupid = 0){

    	$res = array();
    	if ($users = $this->wikipersistor->active_users ($wiki->id, $groupid)){
    		foreach ($users as $user){
    			$res[] = $user->author;
    		}
    	}

    	return $res;
    }
	
	/**
	 * Return the number of participations in the current dfwiki EXCLUDING THE DISCUSSIONS.
	 * 
	 * @param String $user
	 * @param Wiki $wiki
	 * 
	 * @return Integer 
	 * 
	 */
	function user_num_activity ($user,$wiki){
		
		if ($records = $this->wikipersistor->user_num_activity ($wiki->id, $user)){
			$res = $records->num;
		} else {
			$res = 0;
		}
		return $res;
	}
	
	/**
	 * Return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
	 * 
	 * @param Integer $wikiid
	 * @param String $user
	 * 
	 * @return Array of String
	 */
	function user_activity ($wiki, $user){
		
		if ($users = $this->wikipersistor->user_activity($wiki->id, $user)){
			foreach ($users as $user){
				$res[] = $user->pagename;
			}
		}
		return $res;
	}
	
	/**
     *  Returns an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
     *
     *  @param  String 	$user
     *  @param  Integer $wikiid
     *  
     */	
	function user_activity_in_wiki($user, $wikiid){
		$res = array();
		if ($wikipages = $this->wikipersistor->user_activity_in_wiki($user,$wikiid)){
			foreach ($wikipages as $wikipage){
				$res[] = $wikipage->pagename;
			}
		}
		return $res;
	}
	
	/**
	 * returns the maximum version of a page associated with a wiki and a groupid
	 * @param String $pagename
	 * @param int $dfwikiid
	 * @param int $groupid
	 * @return int
	 */
	function get_maximum_value_one ($pagename,$dfwikiid,$groupid) {
		$res = get_record_select('wiki_pages',
		    'pagename=\''.addslashes($pagename).'\' AND dfwiki='.$dfwikiid.' AND groupid='.$groupid,
		    'MAX(version) AS maxim');
	    if (!$res) return false;
	    return $res->maxim;
	}
	
	/**
	 * returns the maximum version of a page associated with a wiki and a groupid and memberid
	 * @param String $pagename
	 * @param int $dfwikiid
	 * @param int $groupid
	 * @param int $memberid
	 * @return int
	 */
	function get_maximum_value_two($pagename,$dfwikiid,$groupid,$memberid) {
		$res = get_record_select('wiki_pages',
	    	'pagename=\''.addslashes($pagename).'\' AND dfwiki='.$dfwikiid.' AND groupid='.$groupid.' AND ownerid='.$memberid,
		    'MIN(version) AS id, MAX(version) AS maxim');
	    if (!$res){
	    	return false;	
	    }
	    if (!$res->maxim){
	    	return 0;	
	    }
	    return $res->maxim;
	}

    /**
     * Return the vote ranking of a wiki.
     *
     * @param Integer $wikiid
     *
     * @return Array of stdClass
     */
    function get_vote_ranking($wikiid) {
        return $this->wikipersistor->get_vote_ranking($wikiid);
    }

    /**
     * Vote a page.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function vote_page($wikiid, $pagename, $pageversion, $username) {
        if ($this->wikipersistor->vote_exists($wikiid, $pagename, $pageversion, $username)) {
            return false;
        }
        return $this->wikipersistor->insert_vote($wikiid, $pagename, $pageversion, $username);
    }



    /**
     * Get array of synonyms of the given page.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  array   Array of wiki_synonym.
     */
    function get_synonyms($pageid) {
        $records = $this->wikipersistor->get_synonyms($pageid->wikiid,
            $pageid->name, $pageid->groupid, $pageid->ownerid);
        return $this->records_to_synonyms($records);
    }

    /**
     * Get array of synonyms in the given wiki.
     *
     * @param   int     $wkiid
     *
     * @return  array   Array of wiki_synonyms.
     */
    function get_synonyms_by_wikiid($wikiid) {
        $records = $this->wikipersistor->get_synonyms_by_wikiid($wikiid);
        return $this->records_to_synonyms($records);
    }

    /**
     * Transforms synonym records to synonym objects.
     *
     * @param   array of stdClass   $records
     *
     * @return  array of wiki_synonym
     */
    function records_to_synonyms($records) {
        $synonyms = array();
        if ($records) {
            foreach ($records as $record) {
                $pageid = new wiki_pageid($record->dfwiki, $record->original,
                    $record->groupid, $record->ownerid);
                $synonyms[] = new wiki_synonym($record->syn, $pageid);
            }
        }
        return $synonyms;
    }


    /**
     * Delete a synonym.
     *
     * @param   wiki_synonym    $synonym
     */
    function delete_synonym($synonym) {
        return $this->wikipersistor->delete_synonym($synonym->pageid->wikiid,
            $synonym->name, $synonym->pageid->groupid, $synonym->pageid->ownerid);
    }

    /**
     * Insert a synonym.
     *
     * @param   wiki_synonym    $synonym
     */
    function insert_synonym($synonym) {
        return $this->wikipersistor->insert_synonym($synonym->pageid->wikiid, $synonym->name,
            $synonym->pageid->name, $synonym->pageid->groupid, $synonym->pageid->ownerid);
    }
    
    /**
     * Get the group id of the user in a course.
     *
     * @param   int     $userid
     * @param   int     $courseid
     *
     * @return  int
     */
    function get_groupid_by_userid_and_courseid($userid, $courseid) {
        $this->wikipersistor->get_groupid_by_userid_and_courseid($userid, $courseid);
    }


    /** Get list of groups in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_groups($courseid) {
        return $this->wikipersistor->get_course_groups($courseid);
    }

    /** Get list of members in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_members($courseid) {
        return $this->wikipersistor->get_course_members($courseid);
    }

    /**
     * Get list of teachers in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_teachers($courseid) {
        $this->wikipersistor->get_course_teachers($courseid);
    }

    /**
     * Incrment the number of hits of the page.
     *
     * @param   wiki_pageid     $pageid
     * @return  bool
     */
    function increment_page_hits($pageid) {
        if (!isset($pageid->version)) {
            $pageid->version = $this->wikipersistor->get_last_wiki_page_version(
                $pageid->name, $pageid->wikiid, $pageid->groupid, $pageid->ownerid);
        }
        return $this->wikipersistor->increment_page_hits($pageid->wikiid,
            $pageid->name, $pageid->version, $pageid->groupid, $pageid->ownerid);
    }

    /**
     * Update the evaliuation of a page.
     *
     * @param   int     $pageid
     * @param   string  $evaluation
     */
    function update_page_evaluation($pageid, $evaluation) {
        return $this->wikipersistor->update_page_evaluation($pageid, $evaluation);
    }

    /**
     * Return array of page versions in the wiki.
     *
     * @param   int     $wikiid
     * @return  array of int
     */
    function get_wiki_page_versions($wikiid) {
        return $this->wikipersistor->get_wiki_page_versions($wikiid);
    }

    /**
     * Get all pages in a wiki.
     *
     * @param   int     $wikiid
     * @return  array
     */
    function get_wiki_pages_by_wikiid($wikiid) {
        return $this->wikipersistor->get_wiki_pages_by_wikiid($wikiid);
    }

    /**
     * Delete an editing lock.
     *
     * @param   int     $lockid
     */
    function delete_lock($lockid) {
        return $this->wikipersistor->delete_lock($lockid);
    }

    /**
     * Get an editing lock.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     *
     * @return  stdClass
     */
    function get_lock($wikiid, $pagename) {
        return $this->wikipersistor->get_lock($wikiid, $pagename);
    }

    /**
     * Insert an editing lock.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     * @param   int     $lockedby
     * @param   int     $lockedsince
     * @param   int     $lockedseen
     */
    function insert_lock($wikiid, $pagename, $lockedby, $lockedsince, $lockedseen) {
        return $this->wikipersistor->insert_lock($wikiid, $pagename,
            $lockedby, $lockedsince, $lockedseen); 
    }
    
    /**
     * return an array with all pages that have a link to the given one.
     * @param String $pagename
     * @return array of wikipages record object
     */
    function get_wiki_page_camefrom ($pagename){
    	return $this->wikipersistor->get_wiki_page_camefrom($pagename); 
    }

    /**
     * Get user information.
     *
     * @param   string  $user
     *
     * @return  stdClass
     */
    function get_user_info($username) {
        return $this->wikipersistor->get_user_info($username);
    }

    /**
     * Get user information.
     *
     * @param   int  $userid
     *
     * @return  stdClass
     */
    function get_user_info_by_id($userid) {
        return $this->wikipersistor->get_user_info_by_id($userid);
    }

    /**
     * Get authors of a wiki page.
     *
     * @param   wiki_pageid     $pageid
     * @return  array of string
     */
    function get_wiki_page_authors($pageid) {
        $records = $this->wikipersistor->get_wiki_page_authors($pageid->wikiid,
           $pageid->name, $pageid->groupid, $pageid->ownerid);
        if (empty($records)) {
            return false;
        }
        $authors = array();
        foreach ($records as $record) {
            $authors[] = $record->author;
        }
        return $authors;        
    }

}
?>
