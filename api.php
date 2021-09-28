<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/plugins/tickets/api.php';

class my_ticketsAPI extends ticketsAPI {
	public function create($request = null, $data = null){
		if($data != null){
			if(!is_array($data)){ $data = json_decode($data, true); }
			if(!isset($data['client'])){ $data['client'] = $this->Auth->User['client']; }
			return parent::create('tickets', $data);
		}
	}
	public function read($request = null, $data = null){
		if($data != null){
			if(!is_array($data)){ $data = json_decode($data, true); }
			if(!isset($data['client'])){ $data['client'] = $this->Auth->User['client']; }
			// Fetch Assigned Clients
			$clients = $this->Auth->query('SELECT * FROM `clients` WHERE `assigned_to` = ? OR `assigned_to` LIKE ? OR `assigned_to` LIKE ? OR `assigned_to` LIKE ?',
				$this->Auth->User['id'],
				$this->Auth->User['id'].';%',
				'%;'.$this->Auth->User['id'],
				'%;'.$this->Auth->User['id'].';%'
			)->fetchAll();
			if($clients != null){ $clients = $clients->all(); }
			// Init Tickets
			$tickets = [];
			// Init Relationships
			$relationships = [];
			// Init Parameters
			$parameters = [];
			// Fetch Relationships
			$statement = 'SELECT * FROM `relationships` WHERE ';
			$statement .= '(`relationship_1` = ? AND `link_to_1` = ? AND (`relationship_2` = ? OR `relationship_3` = ?))';
			$statement .= ' OR ';
			$statement .= '(`relationship_2` = ? AND `link_to_2` = ? AND (`relationship_1` = ? OR `relationship_3` = ?))';
			$statement .= ' OR ';
			$statement .= '(`relationship_3` = ? AND `link_to_3` = ? AND (`relationship_2` = ? OR `relationship_1` = ?))';
			$parameters = [
				'users',$this->Auth->User['id'],'tickets','tickets',
				'users',$this->Auth->User['id'],'tickets','tickets',
				'users',$this->Auth->User['id'],'tickets','tickets',
			];
			if(($data['client'] != '')&&(!empty($data['client']))){
				$statement .= ' OR ';
				$statement .= '(`relationship_1` = ? AND `link_to_1` = ? AND (`relationship_2` = ? OR `relationship_3` = ?))';
				$statement .= ' OR ';
				$statement .= '(`relationship_2` = ? AND `link_to_2` = ? AND (`relationship_1` = ? OR `relationship_3` = ?))';
				$statement .= ' OR ';
				$statement .= '(`relationship_3` = ? AND `link_to_3` = ? AND (`relationship_2` = ? OR `relationship_1` = ?))';
				array_push($parameters,
					'clients',$data['client'],'tickets','tickets',
					'clients',$data['client'],'tickets','tickets',
					'clients',$data['client'],'tickets','tickets'
				);
			}
			if($clients != null){
				foreach($clients as $client){
					$statement .= ' OR ';
					$statement .= '(`relationship_1` = ? AND `link_to_1` = ? AND (`relationship_2` = ? OR `relationship_3` = ?))';
					$statement .= ' OR ';
					$statement .= '(`relationship_2` = ? AND `link_to_2` = ? AND (`relationship_1` = ? OR `relationship_3` = ?))';
					$statement .= ' OR ';
					$statement .= '(`relationship_3` = ? AND `link_to_3` = ? AND (`relationship_2` = ? OR `relationship_1` = ?))';
					array_push($parameters,
						'clients',$client['id'],'tickets','tickets',
						'clients',$client['id'],'tickets','tickets',
						'clients',$client['id'],'tickets','tickets'
					);
				}
			}
			$relations = $this->Auth->query($statement,$parameters)->fetchAll();
			// Creating Relationships Array
			if($relations != null){
				$relations = $relations->all();
				foreach($relations as $relation){
					$relationships[$relation['id']] = [];
					if($relation['relationship_1'] == 'tickets'){ array_push($relationships[$relation['id']],['relationship' => $relation['relationship_1'],'link_to' => $relation['link_to_1'],'created' => $relation['created']]); }
					if($relation['relationship_2'] == 'tickets'){ array_push($relationships[$relation['id']],['relationship' => $relation['relationship_2'],'link_to' => $relation['link_to_2'],'created' => $relation['created']]); }
					if($relation['relationship_3'] == 'tickets'){ array_push($relationships[$relation['id']],['relationship' => $relation['relationship_3'],'link_to' => $relation['link_to_3'],'created' => $relation['created']]); }
				}
			}
			foreach($relationships as $relations){
				foreach($relations as $relation){
					if(!isset($tickets[$relation['link_to']])){
						$ticket = $this->Auth->read('tickets', $relation['link_to']);
						if($ticket != null){
							$ticket = $ticket->all()[0];
							if($ticket['archived'] != 'true'){ $tickets[$ticket['id']] = $ticket; }
						}
					}
				}
			}
			// Init Array
			$raw = [];
			foreach($tickets as $id => $ticket){
				if(!$this->Auth->valid('custom','tickets_charges',1)){
					unset($ticket['charge_to_other']);
					unset($ticket['charge_to_shipper']);
				}
				unset($ticket['active']);
				array_push($raw,$ticket);
			}
			// Format Array
			// Init Results
			$results = [];
			// Format Results
			foreach($raw as $row => $result){
				if(!$this->Auth->valid('custom','tickets_charges',1)){
					unset($raw[$row]['charge_to_other']);
					unset($raw[$row]['charge_to_shipper']);
				}
				unset($raw[$row]['active']);
				$results[$row] = $this->convertToDOM($raw[$row]);
			}
			if(($raw != null)&&(!empty($raw))){
				// Fetch Headers
				$headers = $this->Auth->getHeaders('tickets');
				// Remove columns
				if(!$this->Auth->valid('custom','tickets_charges',1)){
					unset($headers[array_search('charge_to_other', $headers)]);
					unset($headers[array_search('charge_to_shipper', $headers)]);
				}
				unset($headers[array_search('active', $headers)]);
				// Return
				return [
					"success" => $this->Language->Field["This request was successfull"],
					"request" => $request,
					"data" => $data,
					"output" => [
						'headers' => $headers,
						'raw' => $raw,
						'results' => $results,
						"relationships" => $relationships,
						"tickets" => $tickets,
					],
				];
			} else {
				return [
					"success" => $this->Language->Field["This request was successfull"],
					"request" => $request,
					"data" => $data,
					"output" => [
						'headers' => $this->Auth->getHeaders('tickets'),
						'raw' => $raw,
						'results' => $results,
						"relationships" => $relationships,
						"tickets" => $tickets,
					]
				];
			}
		} else {
			return [
				"error" => $this->Language->Field["Unable to complete the request"],
				"request" => $request,
				"data" => $data,
			];
		}
	}
}
