<?php 

/**
 * Page callback.
 */
function emfl_api_unit_tests_run_all() {

  $token = NULL;
  if(file_exists('.access-token')) $token = trim(file_get_contents('.access-token'));
  if(empty($token) && !empty($_REQUEST['apitoken'])) $token = $_REQUEST['apitoken'];
  if(empty($token)) {
    ?>
    <form method="post">
      <input name="apitoken" placeholder="Enter a valid API token" />
      <input type="submit" />
    </form>
    <?php
    die();
  }
  
  define('EMFL_API_TOKEN', $token);
  foreach( glob( __DIR__ . "/tests/*.php") as $filename ) {
    try {
      include $filename;
    } catch(Exception $e) {
      echo '<b style="color:red;">EXCEPTION in tests/' . $filename . ':' . $e->getMessage() . '</b>';
    }
  }

}

/**
 * Helper for the tests.
 * @return Emfl_Platform_API
 */
function emfl_api_get_instance() {
  require_once 'api.class.php';
  $instance = new Emfl_Platform_API(EMFL_API_TOKEN);
  return $instance;
}

/**
 * Helper for the tests.
 * Standard test result output.
 * @param string $test_name
 * @param string[] $successes
 * @param string[] $failures
 */
function emfl_api_test_output_results($test_name, $successes, $failures) {
  echo '<div class="test"><h2>' . $test_name . '</h2>';
  if(!empty($successes)) echo '<div class="success"><h3>SUCCESSES</h3><ol><li>' . implode('</li><li>', $successes) . '</li></ol></div>';
  if(!empty($failures)) echo '<div class="fail"><h3>FAILURES</h3><ol><li>' . implode('</li><li>', $failures) . '</li></ol></div>';
  echo '</div>';
}

?>
<!DOCTYPE html>
<html>
<body>
<style>
.success { color: green; }
.fail { color: red; }
</style>
<?php emfl_api_unit_tests_run_all(); ?>
</body>
</html>
