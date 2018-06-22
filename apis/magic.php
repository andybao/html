<?php
/**
 * Created by andybao [wenyu.bao@gmail.com]
 * Date: 2018-06-19
 * Time: 9:55 PM
 */
include_once('simple_html_dom.php');

$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$query = new MongoDB\Driver\Query([]);

if (isset($_GET['api_key'])) {

    $user_key = $_GET['api_key'];
    $api_flag = false;

    $apis = $mongo->executeQuery("magic.api_keys", $query);
    $api_obj = iterator_to_array($apis);
    $api_array = json_decode(json_encode($api_obj), true);


    foreach ($api_array as $k => $v) {
        if ($user_key == $v['api_key']) {
            $api_flag = true;
        }
    }

    if ($api_flag) {
        if(isset($_GET['id'])) {
            $oid = $_GET['id'];
            try {
                $id = new \MongoDB\BSON\ObjectId($oid);

                $filter = ['_id' => $id];
                $options = [];

                $job_query = new \MongoDB\Driver\Query($filter);
                $row = $mongo->executeQuery('magic.jobs', $job_query);

                $r = iterator_to_array($row);

                $url = $r[0]->url;

                $content = file_get_html($url);

                $t = $content->find("span[id=job_summary]");

                $job = array();

                if (empty($t)) {
                    $t = $content->find("div[class=jobsearch-JobComponent-description]");
                }

                if (empty($t)) {
                    $job['result'] = '';
                } else {
                    $job['result'] = (string)$t[0];
                }

            } catch (InvalidArgumentException $e) {
                $job['result'] = '';
            }
            echo json_encode($job);
        }
        elseif (isset($_GET['job'])) {
            $job_key = $_GET['job'];
            $jobs = array();
            try {
                $job_list = $mongo->executeQuery("magic.jobs", $query);
                $job_obj_list = iterator_to_array($job_list);
                $job_array = json_decode(json_encode($job_obj_list), true);

                foreach ($job_array as $j) {

                    $j['id'] = $j['_id']['$oid'];
                    unset($j['_id']);

                    if (strpos(strtolower($j['title']), $job_key)) {
                        if (!empty($j['summary'])) {
                            array_push($jobs, $j);
                        }
                    } elseif (strpos(strtolower($j['summary']), $job_key)) {
                        array_push($jobs, $j);
                    }
                }
            } catch (InvalidArgumentException $e) {
                array_push($jobs, '');
            }
            echo json_encode($jobs);
        }
        else {
            $arr = array('result' => '');
            echo json_encode($arr);
        }
    }
    else {
        header("HTTP/1.1 401 Unauthorized");
    }

}
else {
    header("HTTP/1.1 401 Unauthorized");
}