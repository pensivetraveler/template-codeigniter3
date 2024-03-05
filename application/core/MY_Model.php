<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    public string $table;
    public string $primaryKey;
    public string $uniqueKey;
    public array $notNullList;
    public array $nullList;
    public array $strList;
    public array $intList;

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->table = '';
        $this->primaryKey = '';
        $this->uniqueKey = '';
        $this->notNullList = [];
        $this->nullList = [];
        $this->strList = [];
        $this->intList = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Query 직접 작성
    |--------------------------------------------------------------------------
    */
    public function getDataQuery($sql,$array)
    {
        return $this->db->query($sql,$array)->row();
    }

    public function getListQuery($sql,$array)
    {
        return $this->db->query($sql,$array)->result();
    }

    public function getCntQuery($sql,$array)
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

    public function querySql($sql, $params, $returnBool = false)
    {
        $insert = (strpos($sql, "INSERT INTO") !== -1);

        $this->db->trans_begin();

        $this->db->query($sql, $params);

        $result = $this->db->trans_status();

        if ($result === false){
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();
        }else{
            if ($returnBool === false){
                $result = ($insert)?$this->db->insert_id():$this->db->affected_rows();
            }
            $this->db->trans_commit();
        }

        return $result;
    }
    /*
    |--------------------------------------------------------------------------
    | Query 빌더 (PDO)
    |--------------------------------------------------------------------------
    */
    public function getDataPDO($table, $select = [], $where = [])
    {
        if(count($select) > 0) $this->db->select($select);
        if($where) $this->db->where($where);
        return $this->db->get($table)->row();
    }

    public function getListPDO($table, $select = [], $where = [])
    {
        if(count($select) > 0) $this->db->select($select);
        if($where) $this->db->where($where);
        return $this->db->get($table)->result_array();
    }

    public function getCntPDO($table, $where = [])
    {
        $this->db->select('COUNT(*) AS cnt');

        if($where)
        {
            $this->db->where($where);
        }

        $result = $this->db->get($table);

        if($result !== FALSE && $result->num_rows() > 0){
            return (int)$result->row()->cnt;
        }else{
            return 0;
        }
    }

    public function addDataPDO($table, $set, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set);

        if($this->db->insert($table)){
            return $this->afterTrans(true, $returnBool);
        }else{
            return $this->db->error();
        }
    }

    public function modDataPDO($table, $set, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set)
            ->where($where);

        if($this->db->update($table)){
            return $this->afterTrans(false, $returnBool);
        }else{
            return $this->db->error();
        }
    }

    public function delDataPDO($table, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db->delete($table, $where);

        return $this->afterTrans(false, $returnBool);
    }

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

    public function afterTrans($insert = true, $returnBool = false)
    {
        $result = $this->db->trans_status();

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
    /*
    |--------------------------------------------------------------------------
    | 기타 테이블 및 컬럼 정보
    |--------------------------------------------------------------------------
    */
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