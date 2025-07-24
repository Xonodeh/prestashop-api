<form action="{$current}&configure={$module_name}" method="post" enctype="multipart/form-data">
    <fieldset>
        <legend>Importer un fichier CSV</legend>
        <label for="csv_file">Fichier CSV</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" />
        <br /><br />
        <button type="submit" name="submit_csv_upload">Importer</button>
    </fieldset>
</form>
