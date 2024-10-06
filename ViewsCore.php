<?php
namespace TypechoPlugin\ViewStat;

use Typecho\Cookie;
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
        $update_period = 3600; // 1 hour
        try{


            $query_obj = $db->fetchObject($db->select(['updated' => 'date', 'views' => 'num'])
                ->from('table.view_summary')
                ->where('table.view_summary.cid =?', $cid)
            );
            
            // if last_total is null, then insert a new record of cid.
            if (is_null($query_obj)) {
                $total = $db->fetchObject($db->select(['COUNT(cid)' => 'num'])
                    ->from('table.view_details')
                    ->where('table.view_details.cid = ?', $cid)
                )->num;
                $db->query($db->insert('table.view_summary')->rows(
                    ['cid'  => $cid, 'updated'  => time(), 'views'  => $total]
                ));
                return $total;
            } else {
                // if last_total is not null, then update the record of cid.
                $last_total_updated = $query_obj->date;
                $last_total = $query_obj->num;
                $current = time();
                if ($current - $last_total_updated > $update_period) {
                    $total = $db->fetchObject($db->select(['COUNT(cid)' => 'num'])
                        ->from('table.view_details')
                        ->where('table.view_details.cid = ? AND accessed >= ?', $cid, $last_total_updated)
                    )->num;
                    
                    if (is_null($total)) {
                        $total  = 0;
                    }

                    $update_total = $total + $last_total;
                    $db->query($db->update('table.view_summary')->rows(
                        ['views'   => $update_total, 'updated'   => time()]
                    )->where('table.view_summary.cid = ?', $cid));
                    return $update_total;
                } else {
                    return $last_total;
                }
            }
        } catch (Db\Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            echo 'Query faild with message:' .$msg .' Code' .$code;
        }
        return -1;
    }

    public static function get_total_views($force=false)
    {
        # get updated time and update if expired.
        $db = Db::get();
        $cookie_key = 'ViewStat_total_views';
        $cookie_value = Cookie::get(key: $cookie_key);
        if (is_null($cookie_value) || $force) {
            try{
                $total = $db->fetchObject($db->select(['SUM(views)' => 'num'])
                    ->from('table.view_summary')
                )->num;
                Cookie::set($cookie_key, $total, 8 * 60 * 60);
                return $total;
            } catch (Db\Exception $e) {
                $code = $e->getCode();
                $msg = $e->getMessage();
                echo 'Query faild with message:' .$msg .' Code' .$code;
            }
        } else {
            return $cookie_value;
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