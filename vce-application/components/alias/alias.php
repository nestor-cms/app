<?php

class Alias extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Alias',
			'description' => 'Alias of another component',
			'category' => 'site'
		);
	}
	
	
	/**
	 * add hook - this has been disabled
	 */
	public function disabled_preload_component() {
		
		$content_hook = array (
		'delete_extirpate_component' => 'Alias::delete_extirpate_component'
		);

		return $content_hook;

	}
	
	/**
	 * delete anything that has an alias_id associated with the component
	 */
	public static function delete_extirpate_component($component_id, $components) {
	
		global $db;
	
		// find all aliases of this component
		$query = "SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='alias_id' and meta_value='" . $component_id . "'";
		$alias_components = $db->get_data_object($query);
		
		foreach ($alias_components as $key=>$value) {
		
			$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $value->component_id. "'";
			$additional_components = $db->get_data_object($query);
		
			// add to sub component list
			$components[] = $additional_components[0];
		
		}
		
		return $components;
		
	}

	
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
		
		// check that an alias_id has been set.
		if (!isset($requested_component->alias_id)) {
			$error_message = "Error: An alias component does not contain an alias_id";
		} else {
		
			// get alias components meta data
			$query = "SELECT * FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $requested_component->alias_id . "' ORDER BY meta_key";
			$component_meta = $vce->db->get_data_object($query, false);
		
			if (empty($component_meta)) {
				// error if no associted component was found
				$error_message = "Error: An alias component points to another component that cannot be found";		
			} else {
		
				foreach ($component_meta as $meta_data) {
				
					// create a var from meta_key
					$key = $meta_data['meta_key'];
		
					// prevent specific meta_data from overwriting
					if (in_array($key, array('created_at','created_by'))) {
						continue;
					}

					// add meta_value
					$requested_component->$key = $vce->db->clean($meta_data['meta_value']);

					//adding minutia if it exists within database table
					if (!empty($meta_data['minutia'])) {
						$key .= "_minutia";
						$requested_component->$key = $meta_data['minutia'];
					}
			
				}

			}
		
		}

		// if there is an error, display message and a delete button
		if (isset($error_message)) {
			
			$content = '<div class="form-message form-error">' . $error_message . '&nbsp;&nbsp;';

			if ($vce->page->can_delete($requested_component)) {

				// the instructions to pass through the form
				$dossier = array(
				'type' => $requested_component->type,
				'procedure' => 'delete',
				'component_id' => $requested_component->component_id,
				'created_at' => $requested_component->created_at
				);

				// generate dossier
				$dossier_for_delete = $vce->generate_dossier($dossier);

				$content .= <<<EOF
<form id="delete_$requested_component->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;
			
			}
			
			$content .=  '</div>';

			$vce->content->add('premain',$content);
			
		}
		
		return true;
	}
	

	/**
	 * custom create component
	 */
	protected function create($input) {
	
		global $site;
	
		// load hooks
		// alias_create_component
		if (isset($site->hooks['alias_create_component'])) {
			foreach($site->hooks['alias_create_component'] as $hook) {
				$input_returned = call_user_func($hook, $input);
				$input = isset($input_returned) ? $input_returned : $input;
			}
		}
	
		// call to create_component, which returns the newly created component_id
		$component_id = self::create_component($input);

		if ($component_id) {
		
			$input['component_id'] = $component_id;
			
			$response = array(
			'response' => 'success',
			'procedure' => 'create',
			'message' => 'New Component Was Created'
			);

			// load hooks
			// alias_component_created
			if (isset($site->hooks['alias_component_created'])) {
				foreach($site->hooks['alias_component_created'] as $hook) {
					$response_returned = call_user_func($hook, $input, $response);
					$response = isset($response_returned) ? $response_returned : $response;
				}
			}
			
			$site->add_attributes('message','Alias Created');
	
			echo json_encode($response);
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	    
    /**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}


}