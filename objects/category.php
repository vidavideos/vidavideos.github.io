<?php

global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once dirname(__FILE__) . '/../videos/configuration.php';
}

require_once $global['systemRootPath'] . 'objects/bootGrid.php';
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/video.php';

class Category {

    private $id;
    private $name;
    private $clean_name;
    private $description;
    private $iconClass;
    private $nextVideoOrder;
    private $parentId;
    private $type;

    function setName($name) {
        $this->name = xss_esc($name);
    }

    function setClean_name($clean_name) {
        preg_replace('/\W+/', '-', strtolower(cleanString($clean_name)));
        $this->clean_name = xss_esc($clean_name);
    }

    function setNextVideoOrder($nextVideoOrder) {
        $this->nextVideoOrder = $nextVideoOrder;
    }

    function setParentId($parentId) {
        $this->parentId = $parentId;
    }

    function setType($type, $overwriteUserId = 0) {
        global $global;
        $internalId = $overwriteUserId;
        if (empty($internalId)) {
            $internalId = $this->id;
        }
        $exist = false;
        // require this cause of Video::autosetCategoryType - but should be moveable easy here..
        require_once dirname(__FILE__) . '/../objects/video.php';
        $sql = "SELECT * FROM `category_type_cache` WHERE categoryId = ?";
        $res = sqlDAL::readSql($sql, "i", array($internalId));
        $catTypeCache = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($catTypeCache != false) {
            $exist = true;
        }

        if ($type == "3") {
            // auto-cat-type
            Video::autosetCategoryType($internalId);
        } else {
            if ($exist) {
                $sql = "UPDATE `category_type_cache` SET `type` = ?, `manualSet` = '1' WHERE `category_type_cache`.`categoryId` = ?;";
                sqlDAL::writeSql($sql, "si", array($type, $internalId));
            } else {
                $sql = "INSERT INTO `category_type_cache` (`categoryId`, `type`, `manualSet`) VALUES (?,?,'1')";
                sqlDAL::writeSql($sql, "is", array($internalId, $type));
            }
        }
    }

    function setDescription($description) {
        $this->description = xss_esc($description);
    }

    function __construct($id, $name = '') {
        if (empty($id)) {
            // get the category data from category and pass
            $this->name = xss_esc($name);
        } else {
            $this->id = $id;
            // get data from id
            $this->load($id);
        }
    }

    private function load($id) {
        $row = self::getCategory($id);
        if (empty($row))
            return false;
        foreach ($row as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    function loadSelfCategory() {
        $this->load($this->getId());
    }

    function save() {
        global $global;
        if (empty($this->isAdmin)) {
            $this->isAdmin = "false";
        }
        $this->nextVideoOrder = intval($this->nextVideoOrder);
        $this->parentId = intval($this->parentId);
        if (!empty($this->id)) {
            $sql = "UPDATE categories SET name = ?,clean_name = ?,description = ?,nextVideoOrder = ?,parentId = ?,iconClass = ?, modified = now() WHERE id = ?";
            $format = "sssiisi";
            $values = array(xss_esc($this->name), xss_esc($this->clean_name), xss_esc($this->description), $this->nextVideoOrder, $this->parentId, $this->getIconClass(), $this->id);
        } else {
            $sql = "INSERT INTO categories ( name,clean_name,description,nextVideoOrder,parentId,iconClass, created, modified) VALUES (?, ?,?,?,?,?,now(), now())";
            $format = "sssiis";
            $values = array(xss_esc($this->name), xss_esc($this->clean_name), xss_esc($this->description), $this->nextVideoOrder, $this->parentId, $this->getIconClass());
        }
        $insert_row = sqlDAL::writeSql($sql, $format, $values);
        if ($insert_row) {
            if (empty($this->id)) {
                $id = $global['mysqli']->insert_id;
            } else {
                $id = $this->id;
            }
            return $id;
        } else {
            die($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
    }

    function delete() {
        if (!User::isAdmin()) {
            return false;
        }
        // cannot delete default category
        if ($this->id == 1) {
            return false;
        }

        global $global;
        if (!empty($this->id)) {
            $sql = "DELETE FROM categories WHERE id = ?";
        } else {
            return false;
        }
        return sqlDAL::writeSql($sql, "i", array($this->id));
    }

    static function getCategoryType($categoryId) {
        global $global;
        $sql = "SELECT * FROM `category_type_cache` WHERE categoryId = ?;";
        $res = sqlDAL::readSql($sql, "i", array($categoryId));
        $data = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            if (!empty($data)) {
                return $data;
            } else {
                return array("categoryId" => "-1", "type" => "0", "manualSet" => "0");
            }
        } else {
            return array("categoryId" => "-1", "type" => "0", "manualSet" => "0");
        }
    }

    static function getCategory($id) {
        global $global;
        $id = intval($id);
        $sql = "SELECT * FROM categories WHERE id = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "i", array($id));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($result) {
            $result['name'] = xss_esc_back($result['name']);
        }
        return ($res) ? $result : false;
    }

    static function getCategoryByName($name) {
        global $global;
        $sql = "SELECT * FROM categories WHERE clean_name = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($name));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($result) {
            $result['name'] = xss_esc_back($result['name']);
        }
        return ($res) ? $result : false;
    }
    
    static function getOrCreateCategoryByName($name) {
        $cat = self::getCategoryByName($name);
        if(empty($cat)){
            $obj = new Category(0);
            $obj->setName($name);
            $obj->setClean_name($name);
            $obj->setDescription("");
            $obj->setIconClass("");
            $obj->setNextVideoOrder(0);
            $obj->setParentId(0);

            $id = $obj->save();
            return self::getCategoryByName($name);
        }
        return $cat;
    }

    static function getCategoryDefault() {
        global $global;
        $sql = "SELECT * FROM categories ORDER BY id ASC LIMIT 1";
        $res = sqlDAL::readSql($sql);
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($result) {
            $result['name'] = xss_esc_back($result['name']);
        }
        return ($res) ? $result : false;
    }

    static function getAllCategories() {
        global $global, $config;
        if ($config->currentVersionLowerThen('5.01')) {
            return false;
        }
        $sql = "SELECT * FROM categories WHERE 1=1 ";
        if (!empty($_GET['parentsOnly'])) {
            $sql .= "AND parentId = 0 ";
        }
        if (isset($_POST['sort']['title'])) {
            unset($_POST['sort']['title']);
        }
        $sql .= BootGrid::getSqlFromPost(array('name'), "", " ORDER BY name ASC ");
        $res = sqlDAL::readSql($sql);
        $fullResult = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $category = array();
        if ($res) {
            foreach ($fullResult as $row) {
                $row['name'] = xss_esc_back($row['name']);
                $row['total'] = self::getTotalVideosFromCategory($row['id']);
                $category[] = $row;
            }
            //$category = $res->fetch_all(MYSQLI_ASSOC);
        } else {
            $category = false;
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $category;
    }

    static function getChildCategories($parentId) {
        global $global, $config;
        if ($config->currentVersionLowerThen('5.01')) {
            return false;
        }
        $sql = "SELECT * FROM categories WHERE parentId=? AND id!=? ";
        $sql .= BootGrid::getSqlFromPost(array('name'), "", " ORDER BY name ASC ");
        $res = sqlDAL::readSql($sql, "ii", array($parentId, $parentId));
        $fullResult = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $category = array();
        if ($res) {
            foreach ($fullResult as $row) {
                $row['name'] = xss_esc_back($row['name']);
                $row['total'] = self::getTotalVideosFromCategory($row['id']);
                $category[] = $row;
            }
        } else {
            $category = false;
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $category;
    }
    
    static function getChildCategoriesFromTitle($clean_title) {
        $row = self::getCategoryByName($clean_title);
        return self::getChildCategories($row['id']);
    }

    static function getTotalVideosFromCategory($categories_id, $showUnlisted = false) {
        global $global, $config;
        if (!isset($_SESSION['categoryTotal'][$categories_id])) {
            $sql = "SELECT count(id) as total FROM videos v WHERE 1=1 AND categories_id = ? ";
            
            if(User::isLogged()){
                $sql .= " AND (v.status IN ('" . implode("','", Video::getViewableStatus($showUnlisted)) . "') OR (v.status='u' AND v.users_id ='".User::getId()."'))";
            }else{
                $sql .= " AND v.status IN ('" . implode("','", Video::getViewableStatus($showUnlisted)) . "')";
            }
            
            $sql .= Video::getUserGroupsCanSeeSQL();
            
            //echo $categories_id, $sql;exit;
            $res = sqlDAL::readSql($sql, "i", array($categories_id));
            $fullResult = sqlDAL::fetchAllAssoc($res);
            sqlDAL::close($res);
            $total = empty($fullResult[0]['total'])?0:intval($fullResult[0]['total']);
            $rows = self::getChildCategories($categories_id);
            foreach ($rows as $value) {
                $total += self::getTotalVideosFromCategory($value['id']);
            }
            session_write_close();
            session_start();
            $_SESSION['categoryTotal'][$categories_id] = $total;
            session_write_close();
        }
        return $_SESSION['categoryTotal'][$categories_id];
    }

    static function clearCacheCount() {
        // clear category count cache
        session_write_close();
        session_start();
        unset($_SESSION['categoryTotal']);
        //session_write_close();
    }

    static function getTotalCategories() {
        global $global, $config;

        if ($config->currentVersionLowerThen('5.01')) {
            return false;
        }
        $sql = "SELECT id, parentId FROM categories WHERE 1=1 ";
        if (!empty($_GET['parentsOnly'])) {
            $sql .= "AND parentId = 0 OR parentId = -1 ";
        }
        $sql .= BootGrid::getSqlSearchFromPost(array('name'));
        $res = sqlDAL::readSql($sql);
        $numRows = sqlDal::num_rows($res);
        sqlDAL::close($res);
        return $numRows;
    }

    function getIconClass() {
        if (empty($this->iconClass)) {
            return "fa fa-folder";
        }
        return $this->iconClass;
    }

    function setIconClass($iconClass) {
        $this->iconClass = $iconClass;
    }

}
