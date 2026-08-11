<?php

$dir = new RecursiveDirectoryIterator('app/Filament');
$iterator = new RecursiveIteratorIterator($dir);

$translations = [
    "make('name')" => "make('name')->label('Nom')",
    "make('description')" => "make('description')->label('Description')",
    "make('created_at')" => "make('created_at')->label('Créé le')",
    "make('updated_at')" => "make('updated_at')->label('Mis à jour le')",
    "make('status')" => "make('status')->label('Statut')",
    "make('amount')" => "make('amount')->label('Montant')",
    "make('total_amount')" => "make('total_amount')->label('Montant total')",
    "make('notes')" => "make('notes')->label('Notes')",
    "make('date')" => "make('date')->label('Date')",
    "make('user_id')" => "make('user_id')->label('Utilisateur')",
    "make('client_id')" => "make('client_id')->label('Client')",
    "make('chantier_id')" => "make('chantier_id')->label('Chantier')",
    "make('quantity')" => "make('quantity')->label('Quantité')",
    "make('unit_price')" => "make('unit_price')->label('Prix unitaire')",
    "make('type')" => "make('type')->label('Type')",
    "make('reference')" => "make('reference')->label('Référence')",
    "make('email')" => "make('email')->label('Email')",
    "make('phone')" => "make('phone')->label('Téléphone')",
    "make('address')" => "make('address')->label('Adresse')",
    "make('city')" => "make('city')->label('Ville')",
    "make('zip_code')" => "make('zip_code')->label('Code postal')",
    "make('country')" => "make('country')->label('Pays')",
];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        foreach ($translations as $search => $replace) {
            $content = preg_replace("/" . preg_quote($search) . "(?!->label)/", $replace, $content);
        }
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Translated: " . $file->getPathname() . "\n";
        }
    }
}
