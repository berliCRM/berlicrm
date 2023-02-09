<?php

require_once('include/QueryParser/Parser.php');

use PhpMyAdmin\SqlParser\Parser;

class berliQueryParser {
	private $query = "";
	private $hasError = false;
	private $error;
	private $user;
	private $params = array();
	
	function __construct($user, $q, $meta){
		$this->query = $q;
		$this->user = $user;
		$this->meta = $meta;
	}
	
	function parse() {
		$parser = new Parser($this->query);
		if (!empty($parser->errors)) {
			$this->hasError = true;
			$subStr = '';
			if (!empty($parser->errors[0]->token)) {
				$subStr = 'Error near: '.substr($this->query, $parser->errors[0]->token->position, 30);
			}
			
			$this->error = $parser->errors[0]->getMessage()."\n$subStr";
			return false;
		}

		// get module from FROM
		$elementType = $parser->statements[0]->from[0]->table;
		$fromExpr = $parser->statements[0]->from[0]->expr;
		if ($elementType != $fromExpr) {
			$this->error = "Missing WHERE-Keyword";
			return false;
		}

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$queryGenerator = new QueryGenerator($elementType, $currentUserModel);
		$fieldObjects = $queryGenerator->getModuleFields();
		// filter Contacts -> Accounts exception
		if ($elementType == 'Contacts') {
			foreach ($fieldObjects AS $fieldName => $wsField) {
				$fieldModule = getTabModuleName($wsField->getTabId());
				if ($elementType != $fieldModule) {
					unset($fieldObjects[$fieldName]);
				}
			}
		} elseif (in_array($elementType, array('SalesOrder', 'Invoice', 'PurchaseOrder', 'Quotes'))) {
			foreach ($fieldObjects AS $fieldName => $wsField) {
				if ($wsField->getTableName() == 'vtiger_inventoryproductrel') {
					unset($fieldObjects[$fieldName]);
				}
			}
		}
		// fix the mess that is Calendar/Events/Emails
		else if ($elementType == 'Calendar') {
			$queryGenerator->addCondition('activitytype','Task','e','AND');
		} else if ($elementType == 'Events') {
			$queryGenerator->addCondition('activitytype','Task','n','AND');
			$queryGenerator->addCondition('activitytype','Emails','n','AND');
		} else if ($elementType == 'Emails') {
			$queryGenerator->addCondition('activitytype','Emails','e','AND');
			if (isset($fieldObjects['filename'])) {
				unset($fieldObjects['filename']);
			}
			if (isset($fieldObjects['access_count'])) {
				unset($fieldObjects['access_count']);
			}
		}

		// get field list
		$expressions = $parser->statements[0]->expr;
		$fieldList = array();
		$bCountQuery = false;
		foreach ($expressions AS $expr) {
			$fieldName = $expr->column;
			if (empty($fieldName) && $expr->expr == '*') {
				$fieldList = array_keys($fieldObjects);
			} elseif (strtolower($expr->function) == 'count') {
				$bCountQuery = true;
			} else {
				$fieldList[] = $fieldName;
			}
		}
		if (!in_array('id', $fieldList)) {
			$fieldList[] = 'id';
		}

		$queryGenerator->setFields($fieldList);

		// get GROUP BY
		$groups = $parser->statements[0]->group;
		$groupFields = array();
		if ($groups) {
			foreach ($groups AS $field) {
				$fieldName = $field->expr->column;
				$fieldObject = $fieldObjects[$fieldName];
				
				if (!$fieldObject && $fieldName != 'id') {
					$this->hasError = true;
					$this->error = "Invalid field found in GROUP BY: $fieldName";
					return false;
				}
				
				if (!in_array($fieldName, $queryGenerator->getWhereFields())) {
					$queryGenerator->addWhereField($fieldName);
				}
				if ($fieldName == 'id') {
					$table = $this->meta->getEntityBaseTable();
					$fieldName = $this->meta->getIdColumn();
				} else {
					$table = $fieldObject->getTableName();
					$fieldName = $fieldObject->getColumnName();
				}
				$groupFields[] = "$table.$fieldName";
			}
		}

		// get ORDER BY
		$orders = $parser->statements[0]->order;
		$orderFields = array();
		if ($orders) {
			foreach ($orders AS $field) {
				$fieldName = $field->expr->column;
				$type = $field->type;
				$fieldObject = $fieldObjects[$fieldName];
				
				if (!$fieldObject && $fieldName != 'id') {
					$this->hasError = true;
					$this->error = "Invalid field found in ORDER BY: $fieldName";
					return false;
				}
				
				if (!in_array($fieldName, $queryGenerator->getWhereFields())) {
					$queryGenerator->addWhereField($fieldName);
				}
				if ($fieldName == 'id') {
					$table = $this->meta->getEntityBaseTable();
					$fieldName = $this->meta->getIdColumn();
				} else {
					$table = $fieldObject->getTableName();
					$fieldName = $fieldObject->getColumnName();
				}
				$orderFields[] = "$table.$fieldName $type";
			}
		}

		// get WHERE
		$wheres = $parser->statements[0]->where;
		$where = '';
		if ($wheres) {
			foreach ($wheres AS $field) {
				$expr = $field->expr;
				if (!$field->isOperator) {
					foreach ($field->identifiers AS $key => $condition) {
						$fieldName = $condition;
						if ($key == 0) {
							$bReference = false;
							$fieldObject = $fieldObjects[$fieldName];
							
							if (!$fieldObject && $fieldName != 'id') {
								$this->hasError = true;
								$this->error = "Invalid field found in WHERE: $fieldName";
								return false;
							}
							
							if (!in_array($fieldName, $queryGenerator->getWhereFields())) {
								$queryGenerator->addWhereField($fieldName);
							}
							
							// id exception
							if ($fieldName == 'id') {
								$table = $this->meta->getEntityBaseTable();
								$repFieldName = $this->meta->getIdColumn();
								$replaceWith = "$table.$repFieldName";
								$bReference = true;
							} else {
								$table = $fieldObject->getTableName();
								$repFieldName = $fieldObject->getColumnName();
								$replaceWith = "$table.$repFieldName";
								if ($fieldObject->getFieldDataType() == 'reference' || $fieldObject->getFieldDataType() == 'owner') {
									$bReference = true;
								}
							}
							$expr = preg_replace("/$fieldName/", $replaceWith, $expr, 1);
						} else {
							$expr = str_replace("'$fieldName'", '?', $expr);
							if ($bReference) {
								$fieldName = explode('x', $fieldName);
								$fieldName = array_pop($fieldName);
							}
							$this->params[] = $fieldName;
						}
					}
					$where .= "$expr";
				} else {
					$where .= " $expr ";
				}
			}
		}

		$columnList = ($bCountQuery) ? 'count(*)' : $queryGenerator->getSelectClauseColumnSQL();
		$generatedQuery = "SELECT $columnList";
		$generatedQuery .= $queryGenerator->getFromClause();
		$whereClause = $queryGenerator->getWhereClause();
		$generatedQuery .= $whereClause;
		if (!empty($where)) {
			if (empty($whereClause)) {
				$generatedQuery .= " WHERE ($where)";
			} else {
				$generatedQuery .= " AND ($where)";
			}
		}
		if (!empty($groupFields)) {
			$generatedQuery .= ' GROUP BY '.implode(', ', $groupFields);
		}

		if (!empty($orderFields)) {
			$generatedQuery .= ' ORDER BY '.implode(', ', $orderFields);
		}

		// get LIMIT
		$limit = $parser->statements[0]->limit;
		if ($limit) {
			$generatedQuery .= ' LIMIT ';
			$offset = $limit->offset;
			if (!empty($offset)) {
				$generatedQuery .= "$offset, ";
			}
			$rowCount = $limit->rowCount;
			$generatedQuery .= $rowCount;
		}
		$this->query = $generatedQuery;
		
		return true;
	}
	
	function getSql() {
		return $this->query;
	}
	
	function getParams() {
		return $this->params;
	}
	
	function getError() {
		return $this->error;
	}
}