<?php
/**
** activation theme
**/
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
 wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}

class Denis_Metabox {
    private $id;
    private $title;
    private $post_type;
    private $fields = [];
    
    /**
     * Denis_Metabox constructor.
     * @param $id ID de la boite
     * @param $title Titre de la boite
     * @param $post_type Post type
     */
    public function __construct($id,$title,$post_type){
        add_action('admin_init',array(&$this,'create'));
        add_action('save_post',array(&$this,'save'));
        
        $this->id = $id;
        $this->title = $title;
        $this->post_type = $post_type;
        }
        
        public function create(){
            if (function_exists('add_meta_box')){
                add_meta_box($this->id,$this->title,array(&$this,'render'),$this->post_type);
                remove_meta_box("categorydiv", "post", "normal");
                remove_meta_box("postcustom", "post", "normal");
            }
        }
        
        public function save($post_id){
    
            if (((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) || ((defined('DOING_AJAX') && DOING_AJAX) )){
                return false;
            }
            
            foreach($this->fields as $field){
                $meta = $field['id'];
                
                if (isset($_POST[$meta])){
                    $value = $_POST[$meta];
    
                    if (get_post_meta($post_id,$meta)){
                        update_post_meta($post_id,$meta,$value);
                    } else {
                        add_post_meta($post_id,$meta,$value);
                    }
                }
            }
        }
        
        public function render(){
            global $post;

            foreach($this->fields as $field){
                extract($field);
                
                $value = get_post_meta($post->ID,$id,true);
                if ($value == ''){
                    $value = $default;
                }
                
                require __DIR__.'/'.$field['type'].'.php';
            }
        }
        
        public function add($id,$label,$type='text',$default=''){
            $this->fields[] = [
                'id' => $id,
                'name' => $label,
                'type' => $type,
                'default' => $default
            ];

            return $this;
        }

}
// Note: Je peux mettre comme dernier paramètre: post ou page
$box = new Denis_Metabox('pneus','informations','post', 'side', 'high');
$box->add('Ville','Ville: ');

// add_filter('pre_get_posts','custom_search_filter');

function custom_search_filter( $query ) {
	
   // Si on est entrain de faire une recherche
   if ( $query->is_search ) {

	switch( $_GET['search']  ) {
	   
	    case 'revision':
		$query->set( 'post_type', 'revision' );
		break;

	    case 'cat1':
		$query->set( 'category_name','cat1' );
		break;

	    case 'cat2':
		$query->set( 'category_name','cat2' );
		break;
	}
    }
    //print_r($query);
    //exit('<br />ici');
 //global $wp_query; echo $wp_query->found_posts;
 //echo the_search_query();
 //exit();
    return $query;
}


//add_filter('posts_where', 'advanced_search_query' );

function advanced_search_query( $where )
{
  if( is_search() ) {

    global $wpdb;
    $query = get_search_query();
    $query = like_escape( $query );
    //exit($query);
    // $query = BMW
    // 

    // étendre la recherche aux postmeta
    //$where .=" OR {$wpdb->posts}.ID IN (SELECT {$wpdb->inventaire}.post_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'Marque' AND {$wpdb->postmeta}.meta_value LIKE '%$query%' AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id)";
    $where = $wpdb->get_results("SELECT * FROM wp_inventaire WHERE marque = '$query'");
    //$where = "SELECT * FROM wp_inventaire WHERE marque  LIKE '%$query%'";
//print_r($where);
echo the_search_query();
//exit('rien');

    // étendre la recherche aux taxonomies
    //$where .=" OR {$wpdb->posts}.ID IN (SELECT {$wpdb->posts}.ID FROM {$wpdb->posts},{$wpdb->term_relationships},{$wpdb->terms} WHERE {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id AND {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->terms}.term_id AND {$wpdb->terms}.name LIKE '%$query%')";

    if(WP_DEBUG)var_dump($where);
    }
    return $where;
}


//add_filter( 'query_vars', 'willy_add_query_vars' );
function willy_add_query_vars( $vars ){
	$vars[] = "ville";
	$vars[] = "chambres";
	$vars[] = "quartiers";
	$vars[] = "prix-mini";
	$vars[] = "prix-maxi";
	$vars[] = "equipements";
	return $vars;
}

//add_action( 'template_redirect', 'willy_redirect_recherche_ville' );
function willy_redirect_recherche_ville() {
	// s'il s'agit d'une recherche avec la ville de choisie
	if ( is_search() && get_query_var( 'ville' ) 
      // mais que les autres champs son vides
      && ! get_query_var( 'chambres' ) 
      && ! get_query_var( 'quartiers' ) 
      && ! get_query_var( 'prix-mini' ) 
      && ! get_query_var( 'prix-maxi' ) 
      && ! get_query_var( 'equipements' ) ) {
		// et que la ville existe
		if ( $ville = term_exists( get_query_var( 'ville' ) , 'localisation' ) ) {
			// alors on redirige l'utilisateur sur la page du terme de taxonomie
			wp_redirect( get_term_link( $ville['term_taxonomy_id'], 'localisation' ), 303 );
			exit();
		}
	}
}

// Pour supprimer des onglets de l’admin, il faut d’abord ajouter cette ligne dans votre functions.php
add_action( 'admin_menu', 'remove_links_tab_menu_pages' );

/*
  remove_menu_page( 'index.php' );                  //Dashboard
  remove_menu_page( 'jetpack' );                    //Jetpack* 
*/

function remove_links_tab_menu_pages() {
    remove_menu_page('link-manager.php');       // ajouter la fonction qui supprimera les onglets :
    remove_menu_page('edit.php');               // Pour supprimer l’onglet articles:         
    remove_menu_page('upload.php');             // Pour supprimer l’onglet Médias :
    remove_menu_page('edit-comments.php');      // Pour supprimer l’onglet Commentaires 
    remove_menu_page('themes.php');             // Pour supprimer l’onglet Apparence :
    remove_menu_page('plugins.php');            // Pour supprimer l’onglet Extensions :
    remove_menu_page('users.php');              // Pour supprimer l’onglet Utilisateurs :
    remove_menu_page('tools.php');              // Pour supprimer l’onglet Outils :
    remove_menu_page('options-general.php');    // Pour supprimer l’onglet Réglages :
    remove_menu_page( 'edit.php?post_type=page' ); // Pour supprimer l’onglet page:
}
