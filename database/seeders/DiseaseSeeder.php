<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class DiseaseSeeder extends Seeder
{
    public function run()
    {
        //Categories parent
        $this->seedFromCsv_Categoty('crawl_data/benh/Disease_1.csv');

        //Categories child
        $this->seedFromCsv_Categoty('crawl_data/benh/Disease_2.csv');

        //Disease 
        $this->seedFromCsv_Disease('crawl_data/benh/Disease.csv');


    }

    private function seedFromCsv_Categoty($csvFilePath)
    {
        $csvFile = storage_path($csvFilePath);

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0); 

        foreach ($csv as $row) {
            DB::table('categories')->insert([
                'category_name' => $row['category_name'],
                'category_thumbnail' => $row['category_thumbnail'],
                'category_parent_id' => $row['category_parent_id'] ?: null,
                'category_type' => $row['category_type'],
                'category_description' => $row['category_description'],
                'category_is_delete' => $row['category_is_delete'],
                'category_created_at' => now(), 
                'category_updated_at' => now(), 
            ]);
        }
    }
    private function seedFromCsv_Disease($csvFilePath){
        $csvFile = storage_path($csvFilePath);

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0); 

        foreach ($csv as $row) {
            DB::table('diseases')->insert([
                'disease_name' => $row['disease_name'],
                'disease_thumbnail' => $row['disease_thumbnail'],
                'general_overview' => $row['general_overview'],
                'symptoms' => $row['symptoms'],
                'cause' => $row['cause'],
                'risk_subjects' => $row['risk_subjects'],
                'diagnosis' => $row['diagnosis'],
                'prevention' => $row['prevention'],
                'treatment_method' => $row['treatment_method'],
                'disease_is_delete' => 0,
                'disease_is_show' => 0,
                'disease_created_at' => now(), 
                'disease_updated_at' => now(), 
            ]);
        }
    }
}
