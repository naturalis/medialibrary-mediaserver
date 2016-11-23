<?php

namespace nl\naturalis\medialib\server\db\dao;

use \PDO;
use \PDOStatement;
use nl\naturalis\medialib\server\MediaServer;
use nl\naturalis\medialib\util\context\Context;
use Monolog\Logger;

class MediaServerDAO {

	/**
     * @var Logger
     */
	private $_logger;
	/**
     * @var \PDO
     */
	private $_pdo;


	public function __construct(Context $context)
	{
		$this->_pdo = $context->getSharedPDO();
		$this->_logger = $context->getLogger(__CLASS__);
	}


	public function getProducers()
	{
		$sql = 'SELECT DISTINCT producer FROM media ORDER BY producer';
		$stmt = $this->_pdo->prepare($sql);
		$this->_executeStatement($stmt);
		$rs = $stmt->fetchAll(PDO::FETCH_NUM);
		$producers = array();
		foreach($rs as $row) {
			$producers[] = $row[0];
		}
		return $producers;
	}


	public function getMedia($regno)
	{
		$sql = 'SELECT www_dir,www_file,www_ok,master_dir,master_file FROM media WHERE regno=?';
		$stmt = $this->_pdo->prepare($sql);
		$stmt->bindValue(1, $regno);
		$this->_executeStatement($stmt);
		$media = $stmt->fetch(PDO::FETCH_OBJ);
		return $media;
	}


	public function searchMedia($params)
	{
		if(!isset($params) || !is_array($params) || count($params) === 0) {
			$params = self::_getMediaSearchDefaultCriteria();
		}
		if(isset($params['prevPage'])) {
			$params['page'] = max(--$params['page'], 0);
		}
		else if(isset($params['nextPage'])) {
			++$params['page'];
		}
		$sql = 'SELECT * FROM media ';
		$sql .= self::_getMediaSearchWhereClause($params);
		$from = (20 * (int) $params['page']);
		$sql .= " LIMIT $from, 20";
		$stmt = $this->_pdo->prepare($sql);
		self::_bindMediaSearchParams($stmt, $params);
		$this->_executeStatement($stmt);
		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}


	public function countMedia($params)
	{
		if(!isset($params) || !is_array($params) || count($params) === 0) {
			$params = self::_getMediaSearchDefaultCriteria();
		}
		$sql = 'SELECT count(*) FROM media ';
		$sql .= self::_getMediaSearchWhereClause($params);
		$stmt = $this->_pdo->prepare($sql);
		self::_bindMediaSearchParams($stmt, $params);
		$this->_executeStatement($stmt);
		return $stmt->fetchColumn();
	}


	private static function _getMediaSearchWhereClause($params)
	{
		$sql = 'WHERE 1=1';
		if($params['regno']) {
			if(isset($params['regnoExact'])) {
				$sql .= ' AND regno = :regno';
			}
			else {
				$sql .= ' AND regno LIKE :regno';
			}
		}
		if($params['producer']) {
			$sql .= ' AND producer = :producer';
		}
		if($params['fromDate']) {
			$sql .= ' AND source_file_created >= :fromDate';
		}
		if($params['toDate']) {
			$sql .= ' AND source_file_created <= :toDate';
		}
		if(isset($params['backupOk'])) {
			if(isset($params['wwwOk'])) {
				$sql .= ' AND (backup_ok=0 OR www_ok=0)';
			}
			else {
				$sql .= ' AND backup_ok=0';
			}
		}
		else if(isset($params['wwwOk'])) {
			$sql .= ' AND www_ok=0';
		}
		return $sql;
	}


	private static function _bindMediaSearchParams(PDOStatement $stmt, $params)
	{
		if($params['regno']) {
			if(isset($params['regnoExact'])) {
				$stmt->bindValue(':regno', "{$params['regno']}", PDO::PARAM_STR);
			}
			else {
				$stmt->bindValue(':regno', "%{$params['regno']}%", PDO::PARAM_STR);
			}
		}
		if($params['producer']) {
			$stmt->bindValue(':producer', "{$params['producer']}", PDO::PARAM_STR);
		}
		if($params['fromDate']) {
			$stmt->bindValue(':fromDate', "{$params['fromDate']}", PDO::PARAM_STR);
		}
		if($params['toDate']) {
			$stmt->bindValue(':toDate', "{$params['toDate']}", PDO::PARAM_STR);
		}
	}


	private static function _getMediaSearchDefaultCriteria()
	{
		$params = array();
		$params['page'] = 0;
		$params['regno'] = null;
		$params['regnoExact'] = 'on';
		$params['producer'] = null;
		$params['fromDate'] = null;
		$params['toDate'] = null;
		return $params;
	}


	private function _executeStatement(PDOStatement $stmt)
	{
		if($stmt->execute() === false) {
			$info = $stmt->errorInfo();
			throw new \Exception('Database error: ' . $info[2]);
		}
	}

}
