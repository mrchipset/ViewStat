<?php
namespace TypechoPlugin\ViewStat;

use Typecho\Widget;
use Typecho\Db;
use Typecho;
use Widget\ActionInterface;

class ViewsCore extends Widget implements ActionInterface
{

    /**
     * Add views to sepcific cid
     * @return void
     */
    public function action()
    {
        $db = Db::get();
        $options = Widget::widget(('Widget_options'));
    }

    public static function get_cid_views($cid)
    {
        $db = Db::get();
       
        try{
            $total = $db->fetchObject($db->select(['COUNT(cid)' => 'num'])
                ->from('table.view_details')
                ->where('table.view_details.cid = ?', $cid)
            )->num;
            return $total;
        } catch (Db\Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            echo 'Query faild with message:' .$msg .' Code' .$code;
        }
        return -1;
    }

    public static function get_total_views()
    {
        # get updated time and update if expired.
        $db = Db::get();
       
        try{
            $total = $db->fetchObject($db->select(['COUNT(cid)' => 'num'])
                ->from('table.view_details')
            )->num;
            return $total;
        } catch (Db\Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            echo 'Query faild with message:' .$msg .' Code' .$code;
        }
        return -1;
    }

    public static function add_cid_views($cid, $ipv4)
    {
        $db = Db::get();
        $prefix = $db->getPrefix();
        $time = time();
        $sql = <<<SQL
         INSERT INTO typecho_view_details(cid, ipv4, accessed) VALUES ({$cid}, "{$ipv4}", {$time});
        SQL;
        $sql = str_replace("typecho_", $prefix, $sql);
        try{
            $db->query($sql, Db::WRITE);
        } catch (Db\Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            echo 'Create faild with message:' .$msg .' Code' .$code;
        }
    }

}

?>