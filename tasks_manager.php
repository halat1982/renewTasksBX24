<?php
include "Dump.php";

class TasksManager
{
    protected $dealID;
    protected $endDealTime;
    protected const SECURITY_CODE = "9k3H@s7W0rDz4q!";

    
    protected const BX_URL = 'inner hook address with batch method like https://hhhgg.bitrix24.by/rest/.. ... /batch.json';



    public function __construct(array $request)
    {
        $this->setVariables($request);
    }

    public function executeTasksHandlers()
    {
        $filterTime = $this->timeMinus(5);
        $tasks = $this->getTasks($filterTime);
        $tasksIDs = $this->getTaskIDs($tasks);
        $this->renewTasks($tasksIDs);

    }

    public function timeMinus(int $timeAdd): string
    {
        $dt = new \DateTime($this->endDealTime);
        $i = DateInterval::createFromDateString($timeAdd.' seconds');
        $dt->sub($i);
        return $dt->format('Y-m-d H:i:s');
    }

    protected function setVariables(array $request)
    {
        if (isset($request["code"]) && $request["code"] === self::SECURITY_CODE) {
            $this->dealID = $request['dealID'];
            $this->endDealTime = $request['dealTime'];
        } else {
            throw new Exception("Something went wrong");
        }
    }

    protected function getTasks($filterTime): array
    {

        $batch[] =
            'tasks.task.list?' . http_build_query(
                array(
                   "filter" => ["UF_CRM_TASK"=>$this->dealID, ">CLOSED_DATE" =>$filterTime], //"2024-07-29 10:34:20"

                    "select" => array("ID", "TITLE", "CLOSED_DATE")
               )
            );
        $res = $this->executeHook(array('cmd' => $batch));

        return $res;
    }

    protected function getTaskIDs(array $tasks) : array
    {
        $ids = array();

        foreach ($tasks["result"]["result"][0]["tasks"] as $task) {
            $ids[] = $task["id"];
        }

        return $ids;
    }

    protected function renewTasks(array $taskIds)
    {
        if(!empty($taskIds)){
            foreach($taskIds as $taskId){
                $batch[] =
                    'tasks.task.renew?' . http_build_query(
                        array(
                            "taskId" => $taskId
                        )
                    );
            }
            $res = $this->executeHook(array('cmd' => $batch));
        }
    }

    protected function executeHook($params)
    {


        $queryData = http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::BX_URL,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);
        if (!empty($result["result"]["result_error"])) {

            \Extra\Dump::toFile($result["result"]["result_error"], "dumpExecute.php");
        }


        return $result;

    }


}

$tm = new TasksManager($_REQUEST);
$tm->executeTasksHandlers();
