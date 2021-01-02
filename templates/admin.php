<h1>RCF Req Plugin</h1>

<form >
<fieldset>
    <legend>create requirments for all the leagues:</legend>
    <label for="key">New Year:</label>
    <input type="submit" name="createall" value="Create">
  </fieldset>
</form>

<form action="action_page.php">
<fieldset>
    <legend>Copy Latest Requirments:</legend>
    <label for="year">New Year:</label>
    <input type="text" id="year" type="number" min="2000" max="2050" name="year" value="<?php echo date('Y')?>"><br><br>
    <input type="submit" value="Create">
  </fieldset>
</form>