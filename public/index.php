<?
require '../init.php';

use API\Todo;

$todos = Todo::list();
?>
<style>
  table {
    border-collapse:collapse;
  }
  table td {
    border: 1px solid;
  }
</style>
<table>
  <?foreach($todos as $todo){?>
    <tr>
      <td><?=$todo['id']?></td>
      <td><?=$todo['name']?></td>
      <td><?=$todo['description']?></td>
      <td><?=$todo['property']?></td>
    </tr>
  <?}?>
</table>