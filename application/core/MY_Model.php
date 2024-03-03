<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    //결과 값이 하나 일 때
    function queryRow($sql,$array)
    {
        return $this->db->query($sql,$array)->row();
    }

    //리스트로 조회 할 때
    function queryResult($sql,$array)
    {
        return $this->db->query($sql,$array)->result();
    }

    //카운트로 조회 할 때
    function queryCnt($sql,$array)
    {
        return $this->db->query($sql,$array)->row()->cnt;
    }

    public function addSqlSet($dto)
    {
        $sql = " SET ";
        foreach ($dto as $key=>$value){
            $sql .= " {$key} = '{$value}',";
        }
        return substr($sql, 0, -1);
    }

    public function addSqlWhere($dto)
    {
        $sql = " WHERE 1=1 ";
        foreach ($dto as $key=>$value){
            $sql .= " AND {$key} = '{$value}' ";
        }
        return $sql;
    }

    //등록 , 수정, 삭제 시
    function query($sql, $array = [])
    {
        $this->db->trans_begin();

        $this->db->query($sql, $array);

        $result = $this->db->trans_status();

        if ($result === false)
        {
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();
        }
        else
        {
            $this->db->trans_commit();
        }

        return $result;
    }

    public function executeSql($sql, $returnBool = false)
    {
        $insert = (strpos($sql, "INSERT INTO") !== -1);

        $this->db->trans_begin();

        $result = $this->db->query($sql, []);

        if ($this->db->trans_status() === false)
        {
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();
        }
        else
        {
            if ($returnBool === false)
            {
                $result = ($insert)?$this->db->insert_id():$this->db->affected_rows();
            }
            $this->db->trans_commit();
        }

        return $result;
    }

    /**
     * query builder
     */
    public function limit($data)
    {
        if(count($data) > 0){
            if(array_key_exists('limit', $data)){
                $offset = (array_key_exists('offset', $data))?$data['offset']:0;
                $this->db->limit($data['limit'], $offset);
            }
        }
    }

    public function orderBy($data)
    {
        if(count($data) > 0) {
            if(is_array($data[0])){
                foreach ($data as $k=>$v) {
                    $this->db->order_by($v[0], $v[1]);
                }
            }else{
                $this->db->order_by($data[0], $data[1]);
            }
        }
    }

    public function getData($table, $select = [], $where = [])
    {
        if(count($select) > 0) $this->db->select($select);
        if($where) $this->db->where($where);
        return $this->db->get($table)->row();
    }

    public function getList($table, $select = [], $where = [])
    {
        if(count($select) > 0) $this->db->select($select);
        if($where) $this->db->where($where);
        return $this->db->get($table)->result_array();
    }

    public function getCnt($table, $where = [])
    {
        $this->db->select('COUNT(*) AS cnt');

        if($where)
        {
            $this->db->where($where);
        }

        $result = $this->db->get($table);

        if($result !== FALSE && $result->num_rows() > 0){
            return $result->row()->cnt;
        }else{
            return 0;
        }
    }

    public function addData($table, $set, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set);

        if($this->db->insert($table)){
            return $this->afterTrans(true, $returnBool);
        }else{
            echo $this->db->error();
            exit;
//            return $this->db->error();
        }
    }

    public function modData($table, $set, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set)
            ->where($where);

        if($this->db->update($table)){
            return $this->afterTrans(false, true);
        }else{
            return $this->db->error();
        }
    }

    public function delData($table, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db->delete($table, $where);

        return $this->afterTrans(false, true);
    }

    public function afterTrans($insert = true, $returnBool = false)
    {
        $result = $this->db->trans_status();

        if ($this->db->trans_status() === false)
        {
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();

            $result = $this->db->error();
        }
        else
        {
            if ($returnBool === false)
            {
                $result = ($insert)?$this->db->insert_id():$this->db->affected_rows();
            }
            $this->db->trans_commit();
        }

        return $result;
    }

    public function getTableInfo($table)
    {
        return $this->db
            ->where('table_schema', $this->db->database)
            ->where('table_name', $table)
            ->order_by('ordinal_position')
            ->get('INFORMATION_SCHEMA.columns')
            ->result_array();
    }

    public function getColumnList($table)
    {
        $tableInfo = $this->getTableInfo($table);
        return array_column($tableInfo, 'COLUMN_NAME');
    }
}