[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SourcePath,

    [Parameter(Mandatory = $true)]
    [string]$DestinationPath
)

$ErrorActionPreference = 'Stop'
$source = (Resolve-Path -LiteralPath $SourcePath).Path

if ([IO.Path]::GetExtension($source) -ne '.xls') {
    throw 'Le fichier source doit être un classeur Excel .xls.'
}

$provider = @('Microsoft.ACE.OLEDB.16.0', 'Microsoft.ACE.OLEDB.12.0') |
    Where-Object {
        $enumerator = New-Object System.Data.OleDb.OleDbEnumerator
        $null -ne ($enumerator.GetElements() | Where-Object SOURCES_NAME -eq $_)
    } |
    Select-Object -First 1

if (-not $provider) {
    throw 'Le fournisseur Microsoft Access Database Engine (ACE) est requis pour lire ce fichier .xls.'
}

$connectionString = "Provider=$provider;Data Source=$source;Extended Properties='Excel 8.0;HDR=YES;IMEX=1'"
$connection = New-Object System.Data.OleDb.OleDbConnection($connectionString)
$connection.Open()

try {
    $schema = $connection.GetOleDbSchemaTable([System.Data.OleDb.OleDbSchemaGuid]::Tables, $null)
    $sheet = $schema |
        Where-Object { $_.TABLE_TYPE -eq 'TABLE' -and $_.TABLE_NAME -like '*$' } |
        Select-Object -First 1

    if (-not $sheet) {
        throw 'Aucune feuille de données n’a été trouvée dans le classeur.'
    }

    $adapter = New-Object System.Data.OleDb.OleDbDataAdapter(
        "SELECT * FROM [$($sheet.TABLE_NAME)]",
        $connection
    )
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
} finally {
    $connection.Close()
}

$requiredHeaders = @(
    'IA',
    'IEF',
    'MATRICULE',
    'PRENOMS',
    'NOM',
    'DATE NAISSANCE',
    'LIEU DE NAISSANCE',
    'CNI'
)
$columnMap = @{}
foreach ($column in $table.Columns) {
    $columnMap[$column.ColumnName.Trim().ToUpperInvariant()] = $column.ColumnName
}

foreach ($header in $requiredHeaders) {
    if (-not $columnMap.ContainsKey($header)) {
        throw "Colonne obligatoire absente : $header"
    }
}

function Get-CellText([System.Data.DataRow]$Row, [string]$Header) {
    $value = $Row[$columnMap[$Header]]
    if ($value -is [DBNull]) {
        return ''
    }

    return ([string]$value).Trim()
}

function Get-IsoDate([System.Data.DataRow]$Row) {
    $value = $Row[$columnMap['DATE NAISSANCE']]
    if ($value -is [DateTime]) {
        return $value.ToString('yyyy-MM-dd')
    }

    $text = ([string]$value).Trim()
    $parsed = [DateTime]::MinValue
    $formats = @(
        'dd/MM/yyyy',
        'MM/dd/yyyy HH:mm:ss',
        'M/d/yyyy H:mm:ss',
        'yyyy-MM-dd'
    )
    foreach ($format in $formats) {
        if ([DateTime]::TryParseExact(
            $text,
            $format,
            [Globalization.CultureInfo]::InvariantCulture,
            [Globalization.DateTimeStyles]::None,
            [ref]$parsed
        )) {
            return $parsed.ToString('yyyy-MM-dd')
        }
    }

    throw "Date de naissance invalide : $text"
}

$records = foreach ($row in $table.Rows) {
    $record = [ordered]@{
        ia = Get-CellText $row 'IA'
        ief = Get-CellText $row 'IEF'
        matricule = Get-CellText $row 'MATRICULE'
        prenoms = Get-CellText $row 'PRENOMS'
        nom = Get-CellText $row 'NOM'
        date_naissance = Get-IsoDate $row
        lieu_naissance = Get-CellText $row 'LIEU DE NAISSANCE'
        cni = Get-CellText $row 'CNI'
    }

    if (($record.Values -join '').Length -eq 0) {
        continue
    }

    foreach ($key in $record.Keys) {
        if ([string]::IsNullOrWhiteSpace([string]$record[$key])) {
            throw "Valeur obligatoire absente pour « $key » à la ligne Excel $($row.Table.Rows.IndexOf($row) + 2)."
        }
    }

    [pscustomobject]$record
}

$duplicateMatricules = $records | Group-Object { $_.matricule.Trim().ToUpperInvariant() } |
    Where-Object Count -gt 1
$duplicateCnis = $records | Group-Object cni | Where-Object Count -gt 1
if ($duplicateMatricules) {
    throw 'Le fichier contient des matricules en double.'
}
if ($duplicateCnis) {
    throw 'Le fichier contient des CNI en double.'
}

$destinationDirectory = Split-Path -Parent $DestinationPath
if (-not (Test-Path -LiteralPath $destinationDirectory)) {
    New-Item -Path $destinationDirectory -ItemType Directory -Force | Out-Null
}

$records | Export-Csv -LiteralPath $DestinationPath -Delimiter ';' -NoTypeInformation -Encoding UTF8

[pscustomobject]@{
    Source = $source
    Destination = (Resolve-Path -LiteralPath $DestinationPath).Path
    Rows = $records.Count
    Sha256 = (Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash.ToLowerInvariant()
} | ConvertTo-Json
