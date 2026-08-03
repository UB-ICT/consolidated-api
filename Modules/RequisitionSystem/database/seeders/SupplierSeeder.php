<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Models\Bank;
use Modules\RequisitionSystem\Models\SupplierBank;
use Modules\RequisitionSystem\Support\SupplierStatus;

class SupplierSeeder extends Seeder
{
    /**
     * Maps the leading bank code found in the vendor sheet's "Account No."
     * column to the matching Bank record name (seeded by BankSeeder).
     * Longer/more specific codes are matched before shorter ones so e.g.
     * "JPMORGAN CHASE BANK, N.A." isn't swallowed by the shorter "JPM".
     */
    private const BANK_CODE_MAP = [
        'JPMORGAN CHASE BANK, N.A.' => 'JPMorgan Chase Bank, N.A.',
        'BAC INTERNATIONAL BANK' => 'BAC International Bank',
        'BANCO PROMERICA' => 'Banco Promerica',
        'BANK OF AMERICA' => 'Bank of America',
        'JP MORGAN' => 'JPMorgan Chase Bank, N.A.',
        'JPMORGAN' => 'JPMorgan Chase Bank, N.A.',
        'BBL' => 'The Belize Bank Limited',
        'HBL' => 'Heritage Bank Limited',
        'ABL' => 'Atlantic Bank Limited',
        'ATL' => 'Atlantic Bank Limited',
        'WFB' => 'Wells Fargo Bank, N.A.',
        'JPM' => 'JPMorgan Chase Bank, N.A.',
        'BMO' => 'BMO Harris Bank',
        'NCB' => 'National Commercial Bank (Jamaica) Limited',
        'WF'  => 'Wells Fargo Bank, N.A.',
        'CB'  => 'Citibank, N.A.',
        'AB'  => 'Atlantic Bank Limited',
        'BB'  => 'The Belize Bank Limited',
    ];

    public function run(): void
    {
        $approvedStatusId = SupplierStatus::APPROVED_ID;

        foreach ($this->vendors() as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '' || strcasecmp($email, 'none') === 0) {
                $email = 'N/A';
            }

            $taxId = trim((string) ($row['tax_id'] ?? ''));
            if (
                $taxId === ''
                || strcasecmp($taxId, 'N/A') === 0
                || strcasecmp($taxId, 'none') === 0
            ) {
                $taxId = null;
            } elseif (
                Supplier::query()
                    ->where('TAX', $taxId)
                    ->where('name', '!=', $row['name'])
                    ->exists()
            ) {
                // TAX is unique; source sheet has duplicate IDs across vendors.
                $taxId = null;
            }

            $supplier = Supplier::updateOrCreate(
                ['name' => $row['name']],
                [
                    'contact_person' => $row['contact'] ?: 'N/A',
                    'phone_number'   => $row['phone'] ?: ($row['mobile'] ?: 'N/A'),
                    'email'          => $email,
                    'TAX'            => $taxId,
                    'status_id'      => $approvedStatusId,
                ]
            );

            [$bankName, $accountNumber] = $this->resolveBank((string) ($row['account_no'] ?? ''));

            if ($bankName === null) {
                continue;
            }

            $bank = Bank::firstOrCreate(['name' => $bankName]);

            SupplierBank::updateOrCreate(
                ['supplier_id' => $supplier->id],
                [
                    'bank_id'        => $bank->id,
                    'account_number' => $accountNumber !== '' ? $accountNumber : 'N/A',
                    'account_name'   => $row['name'],
                    'address'        => $row['address'] ?? null,
                ]
            );
        }
    }

    /**
     * @return array{0: ?string, 1: string} [bankName, remainingAccountNumber]
     */
    private function resolveBank(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [null, ''];
        }

        foreach (self::BANK_CODE_MAP as $code => $bankName) {
            if (stripos($raw, $code) === 0) {
                $remainder = substr($raw, strlen($code));
                $remainder = ltrim($remainder, " .:#-\t");

                return [$bankName, trim($remainder)];
            }
        }

        // Unrecognized bank code (e.g. "Wire Information") - no bank link created.
        return [null, ''];
    }

    /**
     * Vendor master sheet data (name, account_no, address, email, contact,
     * phone, mobile, tax_id). Net Terms and Category from the source sheet
     * have no matching columns on the suppliers table and are intentionally
     * not imported.
     */
    private function vendors(): array
    {
        return [
            // ---------- Page 1 ----------
            ['name'=>"3T'S Services",'account_no'=>'BBL 271758010120000','address'=>'Jacinto Ville, Toledo District','email'=>'egbertjacobs62@gmail.com','contact'=>'Aaron E. Jacobs','phone'=>'615-9028','mobile'=>null,'tax_id'=>'364985'],
            ['name'=>'501 Enterprise','account_no'=>'BBL 232270010120025','address'=>'38 Turneffe Ave, Belmopan Cayo, Belize','email'=>'connect@501enterprise.com','contact'=>'Charles Burrell','phone'=>'610-0443','mobile'=>null,'tax_id'=>'127836'],
            ['name'=>'A & R Enterprises Limited','account_no'=>'BBL 113500010120001','address'=>'Orange Walk Town, Belize','email'=>'araccounts52@yahoo.com','contact'=>'Nazali Carvajal','phone'=>'613-2925','mobile'=>null,'tax_id'=>'16602'],
            ['name'=>'A&A Cleaning Service','account_no'=>'BBL 270896010120000','address'=>'George Price Highway, San Jose Succotz','email'=>'aacleaningservices4u@gmail.com','contact'=>'Angel Cocom','phone'=>'631-4334','mobile'=>null,'tax_id'=>'190924'],
            ['name'=>'ACROSS','account_no'=>'HBL 33141063','address'=>'109 Bee Lane Spanish Lookout, Cayo Belize','email'=>'proshot@btl.net','contact'=>'John Banman','phone'=>'823-0358','mobile'=>'615-1207','tax_id'=>'031315'],
            ['name'=>'Aero Dispatch Services Ltd.','account_no'=>'HBL 1003501','address'=>'106 Princess Margaret Drive','email'=>'glaingaerodispatch@gmail.com','contact'=>'Giovanni Laing','phone'=>'227-7185','mobile'=>null,'tax_id'=>'5316'],
            ['name'=>'Agro-vet Jiron & Sons','account_no'=>'ABL 100194317','address'=>'Savannah Street San Ignacio, Cayo Belize','email'=>'salessi@agrovetjiron.com','contact'=>'Joshua Jiron','phone'=>'824-3853','mobile'=>null,'tax_id'=>'178751'],
            ['name'=>'Air Technics','account_no'=>'BBL 266513010120000','address'=>"#3 Rio Hondo Street",'email'=>'airtechnicsbze@gmail.com','contact'=>'Rigoberto Xujur','phone'=>'613-4845','mobile'=>null,'tax_id'=>'379920'],
            ['name'=>'Al Ovando','account_no'=>'HBL 2214475','address'=>'10 Samwood Street Dangriga Town','email'=>'obgsaudiogroup@gmail.com','contact'=>'Al Ovando','phone'=>'662-6249','mobile'=>null,'tax_id'=>'360110'],
            ['name'=>"Albert's Tailor Shop",'account_no'=>'ABL 211589093','address'=>'Jesus Alberto Rodriguez #2C St. Martin Avenue, Belmopan, Belize C.A','email'=>'albertrodriguez552@yahoo.com','contact'=>'Jesus Rodriguez','phone'=>'666-7671','mobile'=>null,'tax_id'=>'00-0185404'],
            ['name'=>'Alberta Bol','account_no'=>'BBL 235764020160000','address'=>'San Antonio Village, Toledo District','email'=>'albertabol314@gmail.com','contact'=>'Alberta Bol','phone'=>'614-8618','mobile'=>null,'tax_id'=>'217910'],
            ['name'=>'Alex AC and Electrical','account_no'=>'BBL 145977010120001','address'=>'29 Main Street Punta Gorda','email'=>'vcompbz@gmail.com','contact'=>'Sean Vernon','phone'=>'601-0342','mobile'=>'610-6926','tax_id'=>'38755'],
            ['name'=>'Alexis Salazar','account_no'=>'ABL 210541298','address'=>'6 Tangerine Street Belmopan, Cayo Belize','email'=>'asalazarbze@gmail.com','contact'=>'Alexis Salazar','phone'=>'615-8621','mobile'=>null,'tax_id'=>'77508'],
            ['name'=>'Alfredo Dominguez','account_no'=>'HBL 10-035906','address'=>'1044 Bachelor Ave. Belize City','email'=>null,'contact'=>'Alfredo Dominguez','phone'=>'615-4472','mobile'=>null,'tax_id'=>'29650'],
            ['name'=>'Alindy Gomez','account_no'=>'BBL 236924010220000','address'=>'Yemeri Grove Village','email'=>'gomezcourtney56@gmail.com','contact'=>'Alindy Gomez','phone'=>'602-7754','mobile'=>'611-9191','tax_id'=>'381547'],
            ['name'=>'Allied Tech Distributors Limited','account_no'=>'BBL 160486010120001','address'=>'Trellis Building 1 1/2 Miles Philip Goldson Highway Belize City, Belize C.A','email'=>'areyes@belizealliedtech.com','contact'=>'Alisa Reyes','phone'=>'223-3113','mobile'=>'615-2979','tax_id'=>'198283'],
            ['name'=>'Amandala Press','account_no'=>'ABL 100010677','address'=>'3304 Amandala Drive, Belize City, Belize','email'=>'amandalapress@yahoo.com','contact'=>'Florence Willima','phone'=>'202-4477','mobile'=>null,'tax_id'=>'12990'],
            ['name'=>'Amera Moreira','account_no'=>'BBL 255574010220000','address'=>'Punta Gorda','email'=>null,'contact'=>'Amera Moreira','phone'=>'627-1660','mobile'=>null,'tax_id'=>'368695'],
            ['name'=>'Ana Portillo','account_no'=>'ABL 2120005459','address'=>'Pound Yard Market, Belize City','email'=>null,'contact'=>'Fernanda Tosta','phone'=>'625-8454','mobile'=>'625-8457','tax_id'=>'342101'],
            ['name'=>'Ann-Marie Williams','account_no'=>'BBL 213989010160025','address'=>'Belmopan & Belize City','email'=>'jacymac12@gmail.com','contact'=>'Ann-Marie Williams','phone'=>'615-4040','mobile'=>null,'tax_id'=>'30261'],
            ['name'=>'Anthony Lizama','account_no'=>'ABL 211927646','address'=>'9 George Street, Orange Walk','email'=>'tonylizama28@gmail.com','contact'=>'Anthony Lizama','phone'=>'629-1644','mobile'=>null,'tax_id'=>'224492'],
            ['name'=>'Archlace (Lloyd Carillo)','account_no'=>'ABL 2110031609','address'=>'#25 Iguana Avenue, Belmopan','email'=>'archlace@gmail.com','contact'=>'Lloyd Carillo','phone'=>'604-9677','mobile'=>null,'tax_id'=>'127260'],
            ['name'=>'Arlette Sheppard','account_no'=>'ABL 2110025056','address'=>'9 Flowers Street, Belmopan','email'=>'awadesheppard@gmail.com','contact'=>'Arlette Sheppard','phone'=>'662-2138','mobile'=>'673-2138','tax_id'=>'014080'],
            ['name'=>'Armando Esquiliano','account_no'=>'BBL 141602010220000','address'=>'5466 K Street Kings Park','email'=>'mesquiliano01@gmail.com','contact'=>'Armando Esquiliano','phone'=>'6018840','mobile'=>null,'tax_id'=>'348122'],
            ['name'=>'Arnold Enterprises','account_no'=>'BBL 232322010120025','address'=>'54 Hummingbird Hwy. Belmopan','email'=>'info@arnoldenterprise.com','contact'=>'Joelyn Alvarenga/Sheldon N.C. Arnold','phone'=>'624-5200','mobile'=>'602-8308','tax_id'=>'217337'],
            ['name'=>'Arrow Freight Enterprise Limited','account_no'=>'ABL 100304733','address'=>'22nd Street East, Spanish Lookout, Cayo','email'=>'documents@arrowfreight.bz','contact'=>'Terrel Dueck','phone'=>'615-2380','mobile'=>null,'tax_id'=>'251863'],
            ['name'=>'Art Box Company Ltd.','account_no'=>'ABL 100193504','address'=>'46 Miles George Price Highway, Belmopan','email'=>'accountant@artboxbz.com','contact'=>'Jill Chiang','phone'=>'610-9002','mobile'=>null,'tax_id'=>'123280'],
            ['name'=>'Baking Online','account_no'=>'BBL 196730010120000','address'=>'George Price Highway Santa Elena','email'=>'dinachulin28@gmail.com','contact'=>'Dina Chulin','phone'=>'634-7880','mobile'=>null,'tax_id'=>'248676'],
            ['name'=>'Balderamos Arthurs LLP','account_no'=>'BBL 164297010120003','address'=>'Charter House, Suite 6, 3 1/2 Miles Philip Goldson Highway','email'=>'melissa@balderamosarthurs.com','contact'=>'Melissa Balderamos Mahler','phone'=>'223-3049','mobile'=>'223-3050','tax_id'=>'197712'],
            ['name'=>'Banana Enterprises Limited','account_no'=>'HBL 8125','address'=>'#3 Port Road, Big Creek, Independence','email'=>'accountsreceivables@bigcreekgroup.com','contact'=>'Christine Dominguez','phone'=>'523-2003','mobile'=>'523-2236','tax_id'=>'5307'],
            ['name'=>'BEC LTD.','account_no'=>'ATL 100277889','address'=>'Cor Cleghorn Street, Belize City','email'=>'zmatute@bec.bz','contact'=>'Zarra Matute','phone'=>'223-0641','mobile'=>null,'tax_id'=>'5119'],
            ['name'=>'Belargo Enterprise','account_no'=>'ABL 100075225','address'=>'5615 Lizarraga Avenue, Belize City, Belize','email'=>'belargo@btl.net','contact'=>'Lawrence Craig','phone'=>'223-0862','mobile'=>'605-7948','tax_id'=>'3320'],
            ['name'=>'Belize Biltmore Plaza Limited','account_no'=>'HBL 1248','address'=>'3.5 Miles Philip Goldson Highway, Belize City','email'=>'sales3@belizebiltmore.com','contact'=>'Johann Muy','phone'=>'611-8341','mobile'=>null,'tax_id'=>'5076'],
            ['name'=>'Belize Chamber of Commerce and Industry','account_no'=>'BBL 129738010120001','address'=>'#4792 Coney Drive 1st Floor Withfield Tower PO Box 291 Belize City','email'=>'sraccountsclerk@belize.org','contact'=>'Bibiana Paquil','phone'=>'672-5082','mobile'=>'672-5082','tax_id'=>'5605'],
            ['name'=>'Belize Comex Paint Ltd.','account_no'=>'BBL 135478010120001','address'=>'#9 Front Street, Punta Gorda Town','email'=>'puntagorda@comex.bz','contact'=>'Orlando Mis','phone'=>'722-2266','mobile'=>'612-1865','tax_id'=>'88942'],
            ['name'=>'Belize Communication & Security Ltd.','account_no'=>'BBL 232330010120025','address'=>'2 Unity Boulevard Belmopan City, Cayo Belize','email'=>'lilian.dubon@belizecommunication.com','contact'=>'Lilian Dubon','phone'=>'822-2149','mobile'=>null,'tax_id'=>'15601'],
            ['name'=>'Belize Diesel & Equipment Co. Ltd','account_no'=>'BBL 133654010120003','address'=>'7142 Slaughter House Road Belize City, Belize','email'=>'voliva@belizediesel.com','contact'=>'Vania Martinez','phone'=>'615-6736','mobile'=>null,'tax_id'=>'1594'],
            ['name'=>'Belize Emergency Response Team (B.E.R.T.)','account_no'=>'BBL 134332010120001','address'=>'Belize City, Belize','email'=>'executive.director@bertbelize.ngo','contact'=>'Susan Ferguson','phone'=>'223-3292','mobile'=>null,'tax_id'=>'15463'],
            ['name'=>'Belize Fence Company','account_no'=>'ABL 100307035','address'=>'#272 Center Road Spanish Lookout Belize','email'=>'leroybz@yahoo.com','contact'=>'Regina Huang','phone'=>'614-1942','mobile'=>'614-1942','tax_id'=>'91319'],
            ['name'=>'Belize Formulators Limited','account_no'=>'BBL 129745010120026','address'=>'140 Samuel Haynes Street P.O. Box 1244 Belize City, Belize','email'=>'belizeformulators@gmail.com','contact'=>'Hector Lopez','phone'=>'223-5291','mobile'=>'610-0202','tax_id'=>'5155'],
            ['name'=>'Belize Infrastructure Limited (Civic Center)','account_no'=>'HBL 6131012','address'=>'Belize Infrastructure Limited','email'=>'jessievellos@gmail.com','contact'=>'Jesse Vellos','phone'=>'670-5207','mobile'=>null,'tax_id'=>'194630'],
            ['name'=>'Belize Paradise Shuttle','account_no'=>'ABL 2110018690','address'=>'45 West Collet Canal','email'=>'info@belizeparadiseshuttles.com','contact'=>'Rony Sucuqui','phone'=>'615-9846','mobile'=>'615-8951','tax_id'=>'330179'],
            ['name'=>'Belize Port Authority','account_no'=>'HBL 9131213','address'=>'4 miles George Price Highway, Belize City','email'=>'accountant@bmpa.bz','contact'=>'Claudia Hernandez','phone'=>'223-5695','mobile'=>'627-5635','tax_id'=>'615580'],
            ['name'=>'Belize Ship Handlers Agency Ltd.','account_no'=>'HBL 9131334','address'=>'6480 Mahogany Street, Belize City, Belize','email'=>'info@belizeshiphandlersbz.com','contact'=>'Elsie Gallego','phone'=>'222-4074','mobile'=>null,'tax_id'=>'000440'],
            ['name'=>'Belize Telemedia Limited','account_no'=>'ABL 100195860','address'=>'Belize Telemedia Limited, St. Thomas Street','email'=>'kprice@livedigi.com','contact'=>'Krystal Price','phone'=>'615-0719','mobile'=>null,'tax_id'=>'5112'],
            ['name'=>'Belize Western Energy Ltd.','account_no'=>'BBL 132443010120001','address'=>'#2 Corner St. Luke & St. Charles Street, Belize City, Belize','email'=>'accountsreceivables@bwelbze.com','contact'=>'Alanis Gutierrez','phone'=>'614-1083','mobile'=>null,'tax_id'=>'35'],
            ['name'=>'Belizean Dreams','account_no'=>'BBL 142787010120001','address'=>'Hopkins Village, Stann Creek District','email'=>'receivables@belizeandreams.com','contact'=>'Rene Sanchez','phone'=>'522-2200','mobile'=>null,'tax_id'=>'113969'],
            ['name'=>"Bellavi's Bistro",'account_no'=>'ABL 100312519','address'=>"7560 Doyle's Delight Street, Belmopan, Belize",'email'=>'bellavisbistro@gmail.com','contact'=>'Shahida Vega','phone'=>'614-6300','mobile'=>null,'tax_id'=>'166958'],
            ['name'=>'Belmopan Aggregates & Hardware','account_no'=>'BBL 117681010120001','address'=>'Forest Drive Belmopan, Cayo Belize','email'=>'accounts@bahbz.com','contact'=>'Andy Ek','phone'=>'822-3715','mobile'=>null,'tax_id'=>'15534'],
            ['name'=>'Belmopan Hardware','account_no'=>'BBL 152847010120001','address'=>'#1 Mussel Creek Street, Belmopan','email'=>'belmopanhardware@gmail.com','contact'=>'William Tom','phone'=>'822-0181','mobile'=>null,'tax_id'=>'169459'],
            ['name'=>'BelPrint','account_no'=>'BBL 252842010120000','address'=>'13 Seagull Street, San Pedro, Belize','email'=>'sales@belprint.com','contact'=>'Sinead Staines','phone'=>'628-8081','mobile'=>null,'tax_id'=>'344458'],
            ['name'=>"Benny's Enterprises Limited",'account_no'=>'BBL 132211010120001','address'=>'2.5 Miles Philip Goldson Highway','email'=>'statements@bennysonline.com','contact'=>'Yvette McDougal','phone'=>'223-6236','mobile'=>null,'tax_id'=>'000008'],
            ['name'=>'Berris Torres','account_no'=>'ABL 2120013716','address'=>'4409 Zacaranda St. Belize City','email'=>'berristorres2016@gmail.com','contact'=>'Berris Torres','phone'=>'673-3765','mobile'=>null,'tax_id'=>'091162'],
            ['name'=>'Beya Suites','account_no'=>'BBL 106557010120001','address'=>'Hopeville Punta Gorda Town','email'=>'info@beyasuites.com','contact'=>'Lisa Woodye Avila','phone'=>'722-2188','mobile'=>null,'tax_id'=>'138696'],
            ['name'=>'Big Signs Belize','account_no'=>'BBL 176819010120000','address'=>'Floral Park, Mile 60, George Price, Highway.','email'=>'billboards@bigsignsbelize.com','contact'=>'Linda Jeal','phone'=>'625-5183','mobile'=>null,'tax_id'=>'103847'],

            // ---------- Page 2 ----------
            ['name'=>'Birds Isle Restaurant','account_no'=>'BBL 136930010120001','address'=>'90 Albert Street, Belize City, Belize','email'=>'bi.restaurant22@gmail.com','contact'=>'Vince Young','phone'=>'207-6500','mobile'=>'627-4056','tax_id'=>'24705'],
            ['name'=>'Bloom & Bake Catering','account_no'=>'ABL 2120016812','address'=>"170 St. John's Road, San Martin, Belmopan",'email'=>'lindairmaemmanuel@gmail.com','contact'=>'Linda Emmanuel','phone'=>'623-3077','mobile'=>null,'tax_id'=>'33010'],
            ['name'=>'Blue Coral Management','account_no'=>'BBL 151519010120000','address'=>'San Martin, Belmopan','email'=>'j.miranda@turnefferesort.com','contact'=>'Jose Miranda','phone'=>'677-0156','mobile'=>null,'tax_id'=>'16450'],
            ['name'=>'Blue Creek Hatchery Limited','account_no'=>'BBL 113379010120026','address'=>'Blue Creek Orange Walk','email'=>'breana.bchatchery@gmail.com','contact'=>'Breana Fuentes','phone'=>'674-8825','mobile'=>null,'tax_id'=>'16604'],
            ['name'=>'Blue Spring','account_no'=>'BBL 101101010120001','address'=>'Jasmin Street, Punta Gorda','email'=>'markusjr762@gmail.com','contact'=>'Mariano Kus','phone'=>'612-8928','mobile'=>null,'tax_id'=>'075347'],
            ['name'=>'Blue Tang Inn','account_no'=>'BBL 169583010120001','address'=>'Sand Piper Street - Beachfront San Perdro Town','email'=>'info@bluetanginn.com','contact'=>'Isela Aban','phone'=>'507-519-6000','mobile'=>null,'tax_id'=>'15565'],
            ['name'=>'BlueBelize Boutique B&B','account_no'=>'BBL 106795010120001','address'=>'139 Front Street, Punta Gorda Town','email'=>'book@bluebelize.com','contact'=>'Florette Castro','phone'=>'722-2678','mobile'=>'612-2488','tax_id'=>'128916'],
            ['name'=>'Books School & Office Supplies','account_no'=>'ATL 100308935','address'=>'#20 San Martin Avenue, Belmopan','email'=>'booksbmpl@gmail.com','contact'=>'Jeremiah Tzib','phone'=>'610-4192','mobile'=>null,'tax_id'=>'17147'],
            ['name'=>'Bowen & Bowen Ltd BZE CITY','account_no'=>'HBL 1038879','address'=>'1 King Street, Belize City , Belize','email'=>'customerservice@bowen.bz','contact'=>'Tracy Aragon Ext 261','phone'=>'227-7031','mobile'=>'615-4125','tax_id'=>'20'],
            ['name'=>'Bradley Marine Services','account_no'=>'ABL 100319594','address'=>'Mile 10 George Price Highway, Belize District','email'=>'bbypelican@hotmail.com','contact'=>'Stephen Bradley','phone'=>'632-4646','mobile'=>null,'tax_id'=>'5212'],
            ['name'=>'BRC Printing Ltd.','account_no'=>'BBL 121636010120001','address'=>'#18 Nazarene Street Benque Viejo','email'=>'accounts@brcprinting.com','contact'=>'Ixel Zuniga-Coleman','phone'=>'823-2143','mobile'=>'630-8585','tax_id'=>'93'],
            ['name'=>'Bricks & Books Cafe','account_no'=>'AB 2110039380','address'=>'#17 Regent Street Belize City','email'=>'bricksbooksbz@gmail.com','contact'=>'Bianna Camal','phone'=>'610-3461','mobile'=>null,'tax_id'=>'342074'],
            ['name'=>'Brothers Habet Ltd.','account_no'=>'ABL 100060888','address'=>'115 Barrack Road 4 Mls Western Highway Belize City,','email'=>'piedad@brothershabet.bz','contact'=>'Piedad Menzies','phone'=>'223-5890','mobile'=>null,'tax_id'=>'5183'],
            ['name'=>'BTALCO','account_no'=>'ABL 100141437','address'=>'# 2 North Front Street, Belize City','email'=>'myrtle@btalco.com','contact'=>'Myrthle Fernandez','phone'=>'223-0069','mobile'=>null,'tax_id'=>'73'],
            ['name'=>"Builders' Hardware",'account_no'=>'BBL 195963010120000','address'=>'Constitution Drive, Belmopan','email'=>'accounts@buildershardware.com','contact'=>'Abigail Skeen/Diane Castillo','phone'=>'822-1071','mobile'=>'822-0501','tax_id'=>'5209'],
            ['name'=>'Bulridge Limited','account_no'=>'BBL 124528010120000','address'=>'Mile 61 George Price Highway Belmopan City, Cayo District 00000 Belize C.A','email'=>'receivables@bulridgebelize.com','contact'=>'Elicia Catillo','phone'=>'832-8888','mobile'=>'672-1023','tax_id'=>'134576'],
            ['name'=>'Busa B Band','account_no'=>'BBL 191979010220000','address'=>"Lord's Bank Village",'email'=>'jeromenoralez2@gmail.com','contact'=>'Andron Noralez','phone'=>'611-1213','mobile'=>null,'tax_id'=>'000233469'],
            ['name'=>'Byton Awe','account_no'=>'BBL 218869010160000','address'=>'San Ignacio 18th street','email'=>'brytonlawe@gmail.com','contact'=>'Byton Awe','phone'=>'625-6246','mobile'=>null,'tax_id'=>null],
            ['name'=>'Cahal Pech Village Resort','account_no'=>'BBL 151697010120001','address'=>'Cahal Pech Hill San Ignacio Town Cayo','email'=>'gm@cahalpech.com','contact'=>'Pamela Bradley','phone'=>'610-1623','mobile'=>null,'tax_id'=>'33255'],
            ['name'=>'Caladium Restaurant','account_no'=>'HBL 1043841','address'=>'Lionel del Valle Loop, Market Square, Belmopan.','email'=>'caladiumrestaurant@gmail.com','contact'=>'Selina Pinelo','phone'=>'822-2754','mobile'=>'607-0544','tax_id'=>'359363'],
            ['name'=>'Calxa','account_no'=>'Wire Information','address'=>'Townsville QLD Australia','email'=>'accounts@calxa.com','contact'=>'Mick Devine','phone'=>'613-9016-3447','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>"Canto's Demolition Service",'account_no'=>'ABL 100246529','address'=>'1033 Graduate Crescent Belize City','email'=>'cantosdemolitionservice@outlook.com','contact'=>'Francis Canto','phone'=>'604-5187','mobile'=>null,'tax_id'=>'18325'],
            ['name'=>'Caribbean Best Producers /Publics Supermarket','account_no'=>'ABL 100171127','address'=>'4.5 miles George Price Highway','email'=>'accounts@publicssupermarket.com','contact'=>'Alma Melendez','phone'=>'613-0976','mobile'=>null,'tax_id'=>'135195'],
            ['name'=>'Caribbean Chicken','account_no'=>'ABL 100286619','address'=>'1520 Hummingbird Highway Belmopan','email'=>'salesbmp@caribbeanchicken.com','contact'=>'Jose Jaoquin Navarro','phone'=>'672-0590','mobile'=>null,'tax_id'=>'5225'],
            ['name'=>'Caribbean Shipping Agencies Ltd.','account_no'=>'HBL 9131001','address'=>'117 Albert Street, Belize City','email'=>'sylvia@csabelize.com','contact'=>'Sylvia Mendez','phone'=>'227-7396','mobile'=>'656-1867','tax_id'=>'005335'],
            ['name'=>'Caribbean Sprinter Ltd','account_no'=>'BBL 193306010120000','address'=>'10 N Front Street, Belize City','email'=>'accounting@sprinter.bz','contact'=>'Adam Taylor','phone'=>'223-0033','mobile'=>null,'tax_id'=>'323135'],
            ['name'=>'Caribbean Tire Wholesale','account_no'=>'BBL 133460010120001','address'=>'54 Mile Hummingbird Highway Belmopan City, Cayo','email'=>'opal@caribbeantire.net','contact'=>'Opal Pineda','phone'=>'822-0390','mobile'=>null,'tax_id'=>'015427'],
            ['name'=>'Caribbean Treasures Ltd.','account_no'=>'ABL 2110046043','address'=>'5 Cork Street Belize City, Belize','email'=>'caribtreasures@btl.net','contact'=>'Ray Hohenkirk','phone'=>'223-3354','mobile'=>null,'tax_id'=>'123283'],
            ['name'=>'Caribbean Villas','account_no'=>'BBL 169628010120001','address'=>'San Pedro, Ambergris Caye Belize','email'=>'info@caribbeanvillashotel.com','contact'=>'Jameli Rodas/Yeseni Novelo','phone'=>'226-2715','mobile'=>null,'tax_id'=>'000070'],
            ['name'=>'Caribben Investments Limited','account_no'=>'ABL 0100217258','address'=>'22 Canada Hill Street, Belmopan, Cayo','email'=>'tristan@thebelizecollection.com','contact'=>'Tristan Richards','phone'=>'615-2162','mobile'=>null,'tax_id'=>'072695'],
            ['name'=>'Carolina Biological Supplies','account_no'=>'WF 121000248','address'=>'2700 York Rd Burlington, NC 27215-3398','email'=>'accountsreceivable@carolina.com','contact'=>'Annette Toms','phone'=>'800-334-5551','mobile'=>null,'tax_id'=>'56-0364367'],
            ['name'=>'Casa Pan Dulce','account_no'=>'BBL 127748010120003','address'=>'St. Paul St., Belmopan','email'=>'mark@casapandulce.com','contact'=>'Mark Cuellar','phone'=>'6151728','mobile'=>null,'tax_id'=>'203621'],
            ['name'=>'Cayo One Stop Hardware','account_no'=>'ABL 100290007','address'=>'670 George Price Highway','email'=>'erickkuang@msn.com','contact'=>'Erick Kuang','phone'=>'824-3883','mobile'=>'613-3886','tax_id'=>'179366'],
            ['name'=>'Cayo Pharmaceutical Limited','account_no'=>'ABL 10004388','address'=>'# 9 Caracol Street, San Ignacio Cayo','email'=>'jdbreception@hotmail.com','contact'=>'Vanessa Chan','phone'=>'824-2462','mobile'=>'613-2462','tax_id'=>'17426'],
            ['name'=>'Cayo Precast Ltd.','account_no'=>'BBL 272553010120000','address'=>'Georgeville, Cayo, Belize','email'=>'Cayoprecast@gmail.com','contact'=>'Denver Koop','phone'=>'601-0539','mobile'=>null,'tax_id'=>'366779'],
            ['name'=>'Cayo Rental','account_no'=>'BBL 126002010120026','address'=>'89 Benque Viego Road','email'=>'cayorental@gmail.com','contact'=>'Frank Burns','phone'=>'824-4779','mobile'=>'610-4779','tax_id'=>'84933'],
            ['name'=>'Celebrations','account_no'=>'BBL 173491010120026','address'=>'#30 Queen Street Belize City','email'=>'celebrationsqueenstreet@gmail.com','contact'=>'Lisa Chang','phone'=>'610-3409','mobile'=>null,'tax_id'=>'004484'],
            ['name'=>'Cellular World','account_no'=>'ABL 100145861','address'=>'53 Queen Street, Belize City','email'=>'sgutierrez@cwbze.com','contact'=>'Zully Ventura/Sonia Guttierez','phone'=>'223-5125','mobile'=>'610-9242','tax_id'=>'1301133'],
            ['name'=>'Central Hardware','account_no'=>'BBL 169831010120001','address'=>'Central Hardware, Main Middle Street','email'=>'central.hardware.pg@gmail.com','contact'=>'Kenrick Supaul','phone'=>'615-4024','mobile'=>null,'tax_id'=>'159524'],
            ['name'=>'Central TV & Internet','account_no'=>'BBL 138384010120001','address'=>'Mile 67 Geroge Price Highway Red Creek, Cayo Distritct','email'=>'accounting@centraltv.bz','contact'=>'Ms. Pineda','phone'=>'880-4200','mobile'=>'662-6365','tax_id'=>'158728'],
            ['name'=>'Chaa Creek Ltd','account_no'=>'BBL 121154010120027','address'=>'77 Burns Avenue, San Ignacio','email'=>'accmgr@chaacreek.com','contact'=>'Soraida Polanco','phone'=>'880-2237','mobile'=>null,'tax_id'=>'005262'],
            ['name'=>'Chamberlain Consulting Ltd.','account_no'=>'BBL 163819010120001','address'=>'574 Triggerfish Crescent, Ladyville','email'=>'dionne@chamberlainbelize.com','contact'=>'Dionne Chamberlaine','phone'=>'610-3883','mobile'=>null,'tax_id'=>'203311'],
            ['name'=>'Charles Duncan Jr.','account_no'=>'BBL 273333010160000','address'=>'Olivia Sentino Street, Punta Gorda Town','email'=>'Charlesduncanjr93@gmail.com','contact'=>'Charles Duncan Jr.','phone'=>'664-4270','mobile'=>null,'tax_id'=>'369961'],
            ['name'=>'Charnelle Carissa Hyde','account_no'=>'ABL 211043342','address'=>"85 Lord's Bank Road",'email'=>'cchyde20@gmail.com','contact'=>'Carnelle Hyde','phone'=>'614-2386','mobile'=>null,'tax_id'=>'187523'],
            ['name'=>'Chemical Specialties of Belize','account_no'=>'ABL 100198215','address'=>'521 Buttonwood Bay Blvd., Belize City','email'=>'citruschemrog@gmail.com','contact'=>'Norma Marroquin','phone'=>'223-3635','mobile'=>'223-5659','tax_id'=>'176029'],
            ['name'=>'Christelle M. Wilson','account_no'=>'ABL 2113955159','address'=>'17 Ortanique Street, Belmopan','email'=>'christelle.wilson@ub.edu.bz','contact'=>'Christelle Wilson','phone'=>'675-5486','mobile'=>null,'tax_id'=>'073252'],
            ['name'=>'Christina Wagner','account_no'=>'ABL 2120012045','address'=>'29 4th St. Kings Park Belize City','email'=>'riley9154@gmail.com','contact'=>'Christina Wagner','phone'=>'613-8991','mobile'=>null,'tax_id'=>'2735507'],
            ['name'=>'Christopher William Guydos','account_no'=>'ABL 200580900','address'=>'19 Brian Estate Burrell Boom Village','email'=>'jguydis@yahoo.com','contact'=>'CHristopher Guydos','phone'=>'614-5372','mobile'=>null,'tax_id'=>'233295'],
            ['name'=>'Chukka Belize Limited','account_no'=>'BBL 155091010120001','address'=>'6th Forth Street Suite 101 Belize City, Belize','email'=>'ivernon@chukka.com','contact'=>'Idorine Vernon','phone'=>'610-3694','mobile'=>null,'tax_id'=>'93987'],
            ['name'=>'Cielo Creative Studio and Media','account_no'=>'BBL 237145010120000','address'=>'#5757 Princess Margaret Drive Belize City, Belize C.A','email'=>'admin@cielocreativestudioandmedia.com','contact'=>'Junlin Li (Haidy)','phone'=>'635-5176','mobile'=>null,'tax_id'=>'322449'],
            ['name'=>'Clean Choice','account_no'=>'ABL 100321251','address'=>'#63 Central American Blvd., Belize City','email'=>'j.gongoraaclerks@hotmail.com','contact'=>'Janeli Gongora/Crystal Young','phone'=>'223-7541','mobile'=>'622-2004','tax_id'=>'19185'],
            ['name'=>'Cocina Sabor','account_no'=>'BBL 159056010120001','address'=>'Belize Corozal Road Orange Walk Town','email'=>'cocinasabor@btl.net','contact'=>'Oscar Gutierrez','phone'=>'610-4435','mobile'=>null,'tax_id'=>'191721'],
            ['name'=>"Codd's Pharmacy",'account_no'=>'BBL 155933010120003','address'=>'#2 Benque viejo Road San Ignacio Cayo district','email'=>'daisy71.codd@gmail.com','contact'=>'Deisy Codd','phone'=>'824-3505','mobile'=>'610-1972','tax_id'=>'001445'],
            ['name'=>'Come Explore Belize','account_no'=>'ABL 2110061112','address'=>'#2 Blancaneux Street, San Ignacio','email'=>'hernadav2219@yahoo.com','contact'=>'David Hernandez','phone'=>'665-4598','mobile'=>null,'tax_id'=>'328163'],

            // ---------- Page 3 ----------
            ['name'=>'Coral Cove Inn','account_no'=>'ABL 100267088','address'=>'688 Maya Beach Way, Placencia Stann Creek District, Belize','email'=>'coralcoveinnbelize@gmail.com','contact'=>'Gordon Allen','phone'=>'660-6393','mobile'=>'600-9847','tax_id'=>'341837'],
            ['name'=>'Corona Del Mar','account_no'=>'ABL 2110048268','address'=>'Coconut Drive, San Pedro Town','email'=>'coronadelmarhotel@gmail.com','contact'=>'Janine Lopez','phone'=>'226-2055','mobile'=>'610-4582','tax_id'=>'155216'],
            ['name'=>'Cozy Corner Restaurant','account_no'=>'ABL 100304494','address'=>'Point Placencia','email'=>'cozycorner@btl.net','contact'=>'Jenny Wesby','phone'=>'523-3540','mobile'=>'610-6657','tax_id'=>'004162'],
            ['name'=>'Creative Graphic Impressions','account_no'=>'ABL 100186978','address'=>'Corozal Town, Belize','email'=>'csr1@cgipromos.com','contact'=>'Shanel Zul','phone'=>'322-2282','mobile'=>'635-8086','tax_id'=>'123664'],
            ['name'=>'Cross Caribbean Medical Equipment and Technologies','account_no'=>null,'address'=>'14D Saute Deau Gardens Maraval, Trinidad','email'=>'kirk@c-cmet.com','contact'=>'Kirk Melville','phone'=>'868-392-6990','mobile'=>null,'tax_id'=>'021370683'],
            ['name'=>'CROWE BELIZE LLP','account_no'=>'HBL 10007426','address'=>'35A Regent Street, Belize C.A','email'=>'lisa.zayden@crowe.bz','contact'=>'Lisa Zayden Alvarez','phone'=>'227-6629','mobile'=>null,'tax_id'=>'005426'],
            ['name'=>'Cubola Productions','account_no'=>'BBL 121162010120001','address'=>'35 Elizabeth St. Benque Viejo, DE Cayo District','email'=>'cubola@btl.net','contact'=>'Lilian Vasqez','phone'=>'823-2083','mobile'=>null,'tax_id'=>'026212'],
            ['name'=>'D Pest Control Belize','account_no'=>'BBL 164659010260000','address'=>'21 Panama Street, Belmopan','email'=>'Pestfree87@gmail.com','contact'=>'Fermina Ramos','phone'=>'610-8378','mobile'=>'610-8417','tax_id'=>'68676'],
            ['name'=>'D Roti Spot','account_no'=>'ABL 211407119','address'=>'Belmopan, Cayo','email'=>'keshadunn33@gmail.com','contact'=>'Kesha Dunn','phone'=>'822-0436','mobile'=>'600-0435','tax_id'=>'221050'],
            ['name'=>"D'Family Resturant",'account_no'=>'BBL 244079010120000','address'=>'San Antonio Village, San Ignacio','email'=>'AnerTzib1992@gmail.com','contact'=>'Marianela Tzib','phone'=>'636-3443','mobile'=>null,'tax_id'=>'170875'],
            ['name'=>'Dakers Stationery & Books','account_no'=>'BBL 149144010120001','address'=>'8 Shopping Center, Belmopan','email'=>'dakersstationeryandbooks@gmail.com','contact'=>'Cynthia Enriquez','phone'=>'822-2777','mobile'=>null,'tax_id'=>'123274'],
            ['name'=>'Dan Shipping Services Ltd','account_no'=>'ABL 100173401','address'=>'828 Coney Drive, 2nd Floor, Belize City','email'=>'info@danshippingbelize.com','contact'=>'Dwight Dougal','phone'=>'636-4011','mobile'=>null,'tax_id'=>'148963'],
            ['name'=>'Daniel Chavarria','account_no'=>'BBL 132307010220001','address'=>'7612 Fabers Road Ext. Belize City','email'=>'danielchav69@gmail.com','contact'=>'Daniel Chavarria','phone'=>'625-0911','mobile'=>null,'tax_id'=>'009688'],
            ['name'=>'Danny Sports Gear (Isaias Daniel Umana)','account_no'=>'ABL 2110068136','address'=>'Valley of Peace, Cayo','email'=>'dannysportsgear@gmail.com','contact'=>'Isaias Umaña','phone'=>'632-7112','mobile'=>null,'tax_id'=>'167282'],
            ['name'=>"Danny's Moving Service",'account_no'=>'ABL 210874856','address'=>'Teakettle Village','email'=>'dcordon1975@gmail.com','contact'=>'Daniel Cordon','phone'=>'611-5785','mobile'=>null,'tax_id'=>'000123'],
            ['name'=>'Darel Grant','account_no'=>'BBL 211919010160025','address'=>'#8 Allan Pitts Crescent, Belize City, Belize C.A','email'=>'trevorgaroy@gmail.com','contact'=>'Darel Grant','phone'=>'610-4318','mobile'=>null,'tax_id'=>'008093'],
            ['name'=>'David Alcantara','account_no'=>'ABL 211222372','address'=>'Cotton Tree Village','email'=>'davidalcantara032@gmail.com','contact'=>'David Alcantara','phone'=>'610-7580','mobile'=>null,'tax_id'=>'236895'],
            ['name'=>'David Griffith jr','account_no'=>'ABL 2120023546','address'=>'6 Bermuda Street','email'=>'Youngtrucker773@gmail.com','contact'=>'David Griffith jr','phone'=>'6108940','mobile'=>null,'tax_id'=>'362745'],
            ['name'=>'Day & Night Hotel Belmopan','account_no'=>'BBL 264927010120000','address'=>'7285 George Price Boulevard Belmopan, Cayo','email'=>'daynighthotelbmp@gmail.com','contact'=>'Wanlan (Wanny) Fan','phone'=>'631-6788','mobile'=>null,'tax_id'=>'121440'],
            ['name'=>"De'Burgess",'account_no'=>'ABL 100124170','address'=>'19 Guadalupe Street Orange Walk Town Belize','email'=>'deburgess.1994@gmail.com','contact'=>'Irene Salas','phone'=>'613-05-452','mobile'=>null,'tax_id'=>'2344'],
            ['name'=>"Denfield Borland (Denny's Trucking)",'account_no'=>'ABL 100178512','address'=>'53 Marage Road Ladyville, Belize City','email'=>'dennystrucking@hotmail.com','contact'=>'Elizabeth Borland','phone'=>'670-5347','mobile'=>null,'tax_id'=>'153467'],
            ['name'=>'Design Depot Trading Limited','account_no'=>'BBL 188035010120000','address'=>'3 Miles Philip Golden Highway','email'=>'accounts2@designdepot.bz','contact'=>'Tanya Cano','phone'=>'223-3768','mobile'=>'612-1294','tax_id'=>'246974'],
            ['name'=>'Dibary','account_no'=>'ABL 100237842','address'=>'27 Cardinal Avenue,Belmopan City Cayo District, Belize','email'=>'channel@dibarybelmopan.com','contact'=>'Kimberly Griffin','phone'=>'802-4444','mobile'=>'632-3456','tax_id'=>'192091'],
            ['name'=>'Dolphin Productions','account_no'=>'BBL 130857010120025','address'=>'1053 Graduate Crescent West Landivar Belize City','email'=>'Edita@dolphinbz.com','contact'=>'Edita Pariente','phone'=>'223-1837','mobile'=>null,'tax_id'=>'1194528'],
            ['name'=>'Dondre Augustine/DJ Movements','account_no'=>'BBL 195101010220000','address'=>'15 Amapola Street, Belmopan','email'=>'kaydencarson59@gmail.com','contact'=>'Dondre Augustine','phone'=>'611-2961','mobile'=>'638-1352','tax_id'=>'289454'],
            ['name'=>"Doony's Store",'account_no'=>'ABL 100274338','address'=>'57 Albert Street Belize City','email'=>'varshasunil9@gmail.com','contact'=>'Varsha Sadarangani','phone'=>'615-3112','mobile'=>null,'tax_id'=>'14965'],
            ['name'=>'Dora Cal','account_no'=>'ABL 210776944','address'=>'2 Tiger Avenue, City of Belmopan, Belize','email'=>'doracal2005@gmail.com','contact'=>'Dora Cal','phone'=>'604-5641','mobile'=>null,'tax_id'=>'259580'],
            ['name'=>'Dots Per Inch Ltd.','account_no'=>'HBL 2191','address'=>"#6 St. John's Street Belize City, Belize",'email'=>'info@dpionline.com','contact'=>'Carla McNab','phone'=>'223-1025','mobile'=>null,'tax_id'=>'143321'],
            ['name'=>'Easy Inn Ltd','account_no'=>'ABL 2110044727','address'=>'2 Miles Philip Goldson Highway, Belize City','email'=>'easyinnbz@gmail.com','contact'=>'Vita Lee','phone'=>'223-0380','mobile'=>'633-9326','tax_id'=>'124547'],
            ['name'=>'Edward Alexander Bochub','account_no'=>'ABL 211813616','address'=>'Punta Gorda Town','email'=>'edwardbochub17@gmail.com','contact'=>'Edward Bochub','phone'=>'629-3763','mobile'=>null,'tax_id'=>'98074'],
            ['name'=>'El Gran Mestizo','account_no'=>'ABL 100234426','address'=>'1 Naranjal Street, Orange Walk','email'=>'accounts@hoteldelafuente.bz','contact'=>'Viaqnie Contreras','phone'=>'610-2030','mobile'=>null,'tax_id'=>'215337'],
            ['name'=>"Elsita's Gift Shop",'account_no'=>'BBL 186146010120000','address'=>'28 George Price Street, Punta Gorda, Toledo','email'=>null,'contact'=>'Elsa Alvarez','phone'=>'639-8862','mobile'=>null,'tax_id'=>'158282'],
            ['name'=>'Elton Moore (coach)','account_no'=>'ABL 211758659','address'=>null,'email'=>null,'contact'=>'Elton Moore','phone'=>null,'mobile'=>null,'tax_id'=>'127266'],
            ['name'=>'Elvis Josue Morales','account_no'=>'BBL 142221010220000','address'=>"Santa Elena Town, Bradley's Bank",'email'=>'elvismor93@gmail.com','contact'=>'Elvis Morales','phone'=>'602-1318','mobile'=>null,'tax_id'=>'367041'],
            ['name'=>'Enrique Martinez & Sons Ltd.','account_no'=>'ABL 100264349','address'=>'2 Miles Philip Goldson Highway Belize City','email'=>'info@enriqmart.bz','contact'=>'Aisha Gillett/Kayla Paul','phone'=>'223-2572','mobile'=>'606-4154','tax_id'=>'14983'],
            ['name'=>'Epifania Caliz','account_no'=>'BBL 222784010160000','address'=>'Belmopan City, Cayo','email'=>'epi1988@yahoo.com','contact'=>'Epifania Caliz','phone'=>'602-7857','mobile'=>null,'tax_id'=>'191910'],
            ['name'=>'Era Plaza','account_no'=>'BBL 279992010120000','address'=>'118B George Price Highway','email'=>'eraplazabelize@gmail.com','contact'=>'Pingling Chen','phone'=>'600-9998','mobile'=>null,'tax_id'=>'132058'],
            ['name'=>'Ernroy Caliz','account_no'=>'BBL 168436010220000','address'=>'#3 Street Water Supply Area, Punta Gorda','email'=>'emcaliz94@gmail.com','contact'=>'Emroy Caliz','phone'=>'632-7682','mobile'=>null,'tax_id'=>'319688'],
            ['name'=>"Escander Bedran's Family Hotel (San Ignacio Resort)",'account_no'=>'BBL 121149010120002','address'=>'#18 Buena Vista Street, San Ignacio, Cayo','email'=>'accounts@sanignaciobelize.com','contact'=>'Rachel Salazar','phone'=>'824-2034','mobile'=>null,'tax_id'=>'5214'],
            ['name'=>'Euphrates Auto Rental & Sales Limited','account_no'=>'BBL 134537010120002','address'=>'143 Euphrates Avenue Belize City Belize','email'=>'ears@btl.net','contact'=>'John Singh','phone'=>'227-5752','mobile'=>'610-5752','tax_id'=>'16716'],
            ['name'=>"Ev's Takeout and Catering",'account_no'=>'BBL 264973010120000','address'=>'#2 Xunantunich Street Belmopan','email'=>'evs78vernon@yahoo.com','contact'=>'Evett Vernon','phone'=>'615-4905','mobile'=>null,'tax_id'=>'210458'],
            ['name'=>'Event By Trina Jocobs','account_no'=>'BBL 271004010120000','address'=>'Jacinto Ville, Toledo District','email'=>'jacobs.trina199625@gmail.com','contact'=>'Trina Jacobs','phone'=>'613-4431','mobile'=>null,'tax_id'=>'229687'],
            ['name'=>'Exodus International','account_no'=>'ABL 100107929','address'=>'2 Burns Avenue, San Ignacio, Cayo','email'=>'exodusbze@yahoo.com','contact'=>'Roxana Chulin','phone'=>'824-4400','mobile'=>'824-4401','tax_id'=>'353'],
            ['name'=>'EZY Enterprise','account_no'=>'BBL 239503010120000','address'=>'Center Road, Spanish Lookout','email'=>'ezybelize@gmail.com','contact'=>'Jimmy Braun','phone'=>'6154076','mobile'=>null,'tax_id'=>'259518'],
            ['name'=>"Fabric 'N Fashion",'account_no'=>'BBL 233359010120025','address'=>'Route 20 West, Spanish Lookout','email'=>'claraplett@yahoo.com','contact'=>'Clara Plett','phone'=>'823-0470','mobile'=>'672-1366','tax_id'=>'038290'],
            ['name'=>'Fabrigas Belize Ltd.','account_no'=>'BBL 135221010120026','address'=>'2 3/4 Miles George Price Highway, Belize City','email'=>'wquetzal@fabrigas.bz','contact'=>'Wilma Quetzal/Yesenia Nunez','phone'=>'222-5128','mobile'=>null,'tax_id'=>'61907'],
            ['name'=>"Fabro's Glass Ltd.",'account_no'=>'BBL 233360010120025','address'=>'27 Victoria Street, Belize City','email'=>'fabrosglassltd@yahoo.com','contact'=>'Daniel Fabro','phone'=>'223-5798','mobile'=>null,'tax_id'=>'5427'],
            ['name'=>'Farmers Trading Center','account_no'=>'ABL 100269674','address'=>'Center Road, Spanish Lookout, Belize','email'=>'info@farmerstrading.com','contact'=>'Godon Plett','phone'=>'823-0111','mobile'=>null,'tax_id'=>'5216'],
            ['name'=>'Faustino Yaxcal','account_no'=>'BBL 214067010160025','address'=>'Independence Village, Stann Creek District','email'=>'fyaxcal@ijc.edu.bz','contact'=>'Faustino Yaxcal','phone'=>'675-9789','mobile'=>null,'tax_id'=>'078783'],
            ['name'=>'Federico Rosado','account_no'=>'BBL 126295010140000','address'=>'San Jose Succtoz Village','email'=>'federicorickyrosado@gmail.com','contact'=>'Federico Rosado','phone'=>'630-2525','mobile'=>null,'tax_id'=>'193570'],
            ['name'=>'Felix Coc','account_no'=>'BBL 165408010220001','address'=>'25 3rd Street, San Ignacio','email'=>'felix.coc1964@gmail.com','contact'=>'Felix Coc','phone'=>'609-8355','mobile'=>'625-8355','tax_id'=>'11764'],
            ['name'=>'Femagra Industries Ltd.','account_no'=>'BBL 156861010120026','address'=>'541/2 miles Hummingbird Highway, Belmopan City','email'=>'admin@femagra.com','contact'=>'Jaimy Flores','phone'=>'822-3909','mobile'=>'614-4021','tax_id'=>'16339'],
            ['name'=>'Florasol Flower Shop','account_no'=>'BBL 196549010120000','address'=>'23 Baymen Avenue Belize City','email'=>'belizeflorist@hotmail.com','contact'=>'Gregory Solis','phone'=>'223-3989','mobile'=>null,'tax_id'=>'253966'],

            // ---------- Page 4 ----------
            ['name'=>'Food & Beverage','account_no'=>'ABL 100268871','address'=>'60 New Road Belize City Belize','email'=>'foodbevc@yahoo.com','contact'=>'Martha Apolonio','phone'=>'615-6499','mobile'=>'614-0424','tax_id'=>'5033'],
            ['name'=>'Francis Romero','account_no'=>'BBL 102527010220001','address'=>'Cattle Landing Village, Toledo District','email'=>'rcharters11@gmail.com','contact'=>'Stephanie Romero','phone'=>'675-5791','mobile'=>null,'tax_id'=>'146957'],
            ['name'=>'Friesen Glass Shop','account_no'=>'ABL 100161487','address'=>'Iguana Creek Road, Spanish Lookout','email'=>'friesenglassshop@gmail.com','contact'=>'Cornelius Friesen','phone'=>'671-5008','mobile'=>null,'tax_id'=>'33372'],
            ['name'=>'Fultec Systems','account_no'=>'BBL 131068010120028','address'=>'831 Coney Drive Belize City, Belize','email'=>'shirlena_santos@fultec.com.bz','contact'=>'Shirlena Santos/Shakira Guerra','phone'=>'223-3226','mobile'=>'822-0482','tax_id'=>'15007'],
            ['name'=>'G-RUN Construction','account_no'=>'BBL 233390010120025','address'=>'29 Spain Street, Salvapan, Belmopan City','email'=>'grunconstruction@gmail.com','contact'=>'Cesar A. Geron Sr.','phone'=>'613-4417','mobile'=>null,'tax_id'=>'199102'],
            ['name'=>'G. E. G. Garat Gentle','account_no'=>'BBL 125787010160025','address'=>'Forest Drive Belmopan','email'=>'garyg-559@hotmail.com','contact'=>'Garat Gentle','phone'=>'675-6138','mobile'=>null,'tax_id'=>'123524'],
            ['name'=>'Garbutts Marine Investment Co Ltd','account_no'=>'BBL 106524010120001','address'=>'Taylor Creek, P.G.','email'=>'garbuttsmarineandfishinglodge@gmail.com','contact'=>'Dennis Garbutt','phone'=>'614-3548','mobile'=>null,'tax_id'=>'133195'],
            ['name'=>'GD Brokerage','account_no'=>'ABL 2110013839','address'=>'Corozal Northern Border','email'=>'gdbrokerage01@gmail.com','contact'=>'Nori Duran','phone'=>'607-6449','mobile'=>'611-3059','tax_id'=>'241000'],
            ['name'=>'Gilroy August','account_no'=>'ABL 200432003','address'=>'Belize City','email'=>'Chefgiggs@gmail.com','contact'=>'Gilroy August','phone'=>'628-3436','mobile'=>null,'tax_id'=>'61752'],
            ['name'=>'Gilroy Young','account_no'=>'BBL 217863010220000','address'=>'Indepandence Village','email'=>'gilroy.young@yahoo.com','contact'=>'Gilroy Young','phone'=>'615-0191','mobile'=>null,'tax_id'=>null],
            ['name'=>'Gitz Office Supplies','account_no'=>'BBL 156188010120001','address'=>'Far West Street, San Ignacio','email'=>'david@gitz.bz','contact'=>'David Depaz','phone'=>'613-4489','mobile'=>'624-2741','tax_id'=>'4120'],
            ['name'=>'Glenford Usher','account_no'=>'ABL 211876451','address'=>'4431 Peterseco Street, Belize City','email'=>'usherglendfor0@gmail.com','contact'=>'Glendford Usher','phone'=>'652-1349','mobile'=>null,'tax_id'=>'206048'],
            ['name'=>'Glenisha Guy','account_no'=>'ABL 211428659','address'=>'Roaring Creek Village, Cayo','email'=>'treatsbygeebelize@gmail.com','contact'=>'Glenisha Guy','phone'=>'614-9229','mobile'=>null,'tax_id'=>'155695'],
            ['name'=>'Glenn Robateau','account_no'=>'ABL 211405665','address'=>'Belize City','email'=>null,'contact'=>'Glenn Robateau','phone'=>null,'mobile'=>null,'tax_id'=>null],
            ['name'=>'GLOBAL STEEL SERVICES LTD','account_no'=>'BBL 159658010120026','address'=>'Mile 42.7 George Price Highway, Cayo District','email'=>'info@globalsteelservices.bz','contact'=>'Jocelyn Perez/Tito Romero','phone'=>'623-2444','mobile'=>'610-4117','tax_id'=>'217562'],
            ['name'=>'Global Technologies','account_no'=>'JPMorgan 608552256','address'=>'10388 W State Road 84, SUite 111, Davie, Florida 33324, United States','email'=>'international@gtsimulators.com','contact'=>'Juliana Laska','phone'=>'954-370-7101','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>'Glow Tech Company Ltd.','account_no'=>'ABL 100322919','address'=>'2 miles Philip Goldson Highway, Belize City','email'=>'belizemylights@gmail.com','contact'=>'Sachin Patharnia','phone'=>'601-7886','mobile'=>'602-3435','tax_id'=>'253023'],
            ['name'=>'Go Home','account_no'=>'ABL 100141981','address'=>'3.5 Mls. Phillip Goldson Highway','email'=>'gowirelessdirectlimited@gmail.com','contact'=>'Wicky Thadhani','phone'=>'614-0707','mobile'=>'631-4767','tax_id'=>'191303'],
            ['name'=>'Godfrey Alford','account_no'=>'BBL 142430010220001','address'=>'#1 Lakeview Garden','email'=>'godfrey_alford@yahoo.com','contact'=>'Godfrey Alford','phone'=>'613-6233','mobile'=>null,'tax_id'=>'159150'],
            ['name'=>'Godrey Arzu','account_no'=>'HBL 111-1454','address'=>'Indepandence Village','email'=>'garzu@sjc.edu.bz','contact'=>'Godfrey Arzu','phone'=>'636-3272','mobile'=>null,'tax_id'=>'101387'],
            ['name'=>'Grace Kennedy Bze Ltd.','account_no'=>'BBL 129908010120001','address'=>'2 1/2 Miles Northern Highway Belize City, Belize','email'=>'randy.canul@gkco.com','contact'=>'Randy Canul','phone'=>'223-0572','mobile'=>null,'tax_id'=>'000002'],
            ['name'=>'Grace Restaurant','account_no'=>'BBL 156438010120001','address'=>'Corner King & 21 Main Street Punta Gorda Town','email'=>'gracemcp@hotmail.com','contact'=>'Pallavi Mahung','phone'=>'624-6466','mobile'=>null,'tax_id'=>'1923'],
            ['name'=>'Graduation Source','account_no'=>'JPM 897893959','address'=>'200 Pemberwick Rd Greenwich CT 06831','email'=>'support@graduationsource.com','contact'=>'Customer Care Team','phone'=>'914-934-5991','mobile'=>null,'tax_id'=>'20-4978920'],
            ['name'=>'Great Belize Productions Ltd.','account_no'=>'BBL 129909010120001','address'=>'2882 Coney Dr. Belize City','email'=>'egahona@channel5belize.com','contact'=>'Edith Gahona','phone'=>'670-9106','mobile'=>'223-0146','tax_id'=>'5096'],
            ['name'=>'Greco Sales International','account_no'=>'CB 267084131','address'=>'3250 NE 1ST Avenue, Miami FL 33137','email'=>'william@grecointernational.com','contact'=>'William Sandoval','phone'=>'305-767-4947','mobile'=>null,'tax_id'=>'611617826'],
            ['name'=>'Gretchin Fatima Fernandez','account_no'=>'BBL 236963010220000','address'=>'1277 Sundial Avenue, Coral Grove, Belize Belize City','email'=>'gretchinfatima92@gmail.com','contact'=>'Gretchin Fatima Fernandez','phone'=>'601-4847','mobile'=>null,'tax_id'=>'249334'],
            ['name'=>'Grupo SG Internacional S.A','account_no'=>'BAC International Bank 100385715','address'=>'Panama/Guatemala','email'=>'jason.guerra@grupo-sg.com','contact'=>'Jason Guerra','phone'=>'502-3719-4358','mobile'=>null,'tax_id'=>'DV 67'],
            ['name'=>'GS-Com Belmopan','account_no'=>'BBL 111500010120001','address'=>'Unit 16 Garden City Plaza Belmopan City, Cayo Belize','email'=>'reuben@gs-com.bz','contact'=>'Reuben Perdomo','phone'=>'822-2588','mobile'=>'621-3766','tax_id'=>'92762'],
            ['name'=>'GTSIMULATORS BY GLOBAL TECHNOLOGIES','account_no'=>'JPMORGAN CHASE BANK, N.A.','address'=>null,'email'=>'international@gtsimulators.com','contact'=>'Juliana Laska','phone'=>'954-370-7101','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>"Guerra's Engineering Ltd.",'account_no'=>'ABL 100142203','address'=>'Corner Applestar/Durgeon Dr., Belize City, Belize','email'=>'receivables@guerraengineering.com','contact'=>'Geraldine Pook','phone'=>'223-2587','mobile'=>null,'tax_id'=>'103531'],
            ['name'=>'H & M General Services Co. Ltd','account_no'=>'ABL 100279841','address'=>'Joseph Andrews Drive, San Ignacio Town','email'=>'hmgeneralservicesltd@gmail.com','contact'=>'Adib Hegar','phone'=>'615-3323','mobile'=>'613-1659','tax_id'=>'223513'],
            ['name'=>'Habet & Habet','account_no'=>'BBL 136224010120001','address'=>'107 Cemetery Road Belize City, Belize','email'=>'accounts@habetandhabet.com','contact'=>'Marcia Aldana','phone'=>'227-7459','mobile'=>'227-5575','tax_id'=>'005030'],
            ['name'=>'Happy Maids Cleaning Service','account_no'=>'BBL 120701010120026','address'=>'21 Canada Hill Street, Belmopan','email'=>'happy_mds@yahoo.com','contact'=>'Carren Williams','phone'=>'610-4419','mobile'=>null,'tax_id'=>'061256'],
            ['name'=>'Hello Roses','account_no'=>'BBL 258884010120000','address'=>'2 Guatemala Street, Belmopan, Cayo District Belize','email'=>'hellorosesbmp@gmail.com','contact'=>'Danelie Pott','phone'=>'671-3099','mobile'=>'802-3262','tax_id'=>'127294'],
            ['name'=>"Hode's Place",'account_no'=>'BBL 146781010120001','address'=>'Savannah Road','email'=>'hodesplace501@gmail.com','contact'=>'Carlo Burns','phone'=>'804-2522','mobile'=>'267-3443','tax_id'=>'16206'],
            ['name'=>'Hokol Kin Guesthouse','account_no'=>'ABL 100171804','address'=>'89 4th Avenue, Corozal','email'=>'hokolkin@gmail.com','contact'=>'Sarita Clarke/Aaron Navarro','phone'=>'422-3329','mobile'=>null,'tax_id'=>'142316'],
            ['name'=>"Home & Garden D'Lights",'account_no'=>'BBL 233467010120025','address'=>'61 Main Street P.O Box 102 Orange Walk Town','email'=>'homeandgardenlights@gmail.com','contact'=>'Ena Matinez','phone'=>'614-7051','mobile'=>'615-0256','tax_id'=>'017234'],
            ['name'=>'Hotel De La Fuente','account_no'=>'ABL 211190384','address'=>'14 Main St. Orange Walk Town Orange Walk','email'=>'accounts@hoteldelafuente.bz','contact'=>'Melissa Castillo','phone'=>'614-2280','mobile'=>null,'tax_id'=>'237345'],
            ['name'=>'Ice Point','account_no'=>'ABL 2110018238','address'=>'#22 Stann Creek Street, Belmopan','email'=>'elva770@hotmail.com','contact'=>'Chi-Kuo Lin','phone'=>'822-0681','mobile'=>'604-5849','tax_id'=>'357669'],
            ['name'=>"Iliana's Cafe & Diner",'account_no'=>'ABL 2110031699','address'=>"Anna Smith #5 Iliana Drive, Belmopan",'email'=>'brianna0smith@yahoo.com','contact'=>'Brianna Hernandez','phone'=>'606-6719','mobile'=>null,'tax_id'=>'18577913'],
            ['name'=>'Independence Junior College','account_no'=>'HBL 114-1002','address'=>'Independence Village','email'=>'info@ijc.edu.bz','contact'=>'Mrs. Marie Scott-Young','phone'=>'523-2566','mobile'=>null,'tax_id'=>'336916'],
            ['name'=>'Indira Pascascio','account_no'=>'BBL 124423010220001','address'=>'Punta Gorda Town','email'=>'indira.pascascio@yahoo.com','contact'=>'Indira Pascascio','phone'=>'676-9008','mobile'=>'624-0790','tax_id'=>'250402'],
            ['name'=>'Irah Construction Limited','account_no'=>'ABL 100236781','address'=>'19 Montalvo Avenue, Las Flores, Belmopan','email'=>'Manuel.iraheta@yahoo.com','contact'=>'Mauel Iraheta','phone'=>'610-1002','mobile'=>null,'tax_id'=>'190972'],
            ['name'=>'Irving Agro Supplies','account_no'=>'HBL 4141249','address'=>'Tambran Street, Bradleys Bank, Santa Elena Town','email'=>'ivt.irving@gmail.com','contact'=>'Lionel Irving','phone'=>'601-3322','mobile'=>null,'tax_id'=>'369463'],
            ['name'=>'Isaak Penner','account_no'=>'BBL 212971010140025','address'=>'Forest Home','email'=>'isaakpenner@gmail.com','contact'=>'Isaac Penner','phone'=>'610-4489','mobile'=>null,'tax_id'=>'172989'],
            ['name'=>'Isaac Ranguy','account_no'=>'BBL 16296701020001','address'=>'Punta Gorda Town','email'=>'ranguybismark@gmail.com','contact'=>'Isaac Ranguy','phone'=>'670-3898','mobile'=>null,'tax_id'=>'294466'],
            ['name'=>'Izaks Wireless','account_no'=>'ABL 100288350','address'=>'La Rosita, Blue Creek, Orange Walk','email'=>'marthafroese@gmail.com','contact'=>'Isaak Froese','phone'=>'672-6101','mobile'=>'671-7800','tax_id'=>'1040'],
            ['name'=>"J McClaren's Automotive Services",'account_no'=>'HBL 9141219','address'=>'26 Banana Bank Road','email'=>'jmcclarenautoservice@gmail.com','contact'=>'Jenelle Griffith','phone'=>'615-5916','mobile'=>null,'tax_id'=>'369451'],
            ['name'=>'J.D.B. Limited','account_no'=>'ABL 10004388','address'=>'# 9 Caracol Street San Ignacio Cayo District','email'=>'jdbreception@hotmail.com','contact'=>'Vanessa Chan','phone'=>'824-2462','mobile'=>'613-2462','tax_id'=>'5063'],
            ['name'=>"Jal's Travel Services",'account_no'=>'ABL 100295100','address'=>'148 North Front Street, Belize City, Belize','email'=>'jalstravel@hotmail.com','contact'=>'Ligia Riverol','phone'=>'223-5405','mobile'=>'602-6019','tax_id'=>'2772'],
            ['name'=>"Jamel's Dinner",'account_no'=>'BBL 196201010220000','address'=>'George Price Street, Punta Gorda Town','email'=>'jevonparham91@yahoo.com','contact'=>'Jevon Parham','phone'=>'625-2506','mobile'=>null,'tax_id'=>'207218'],
            ['name'=>'James Brodie & Co. Ltd.','account_no'=>'BBL 129949010110007','address'=>'16 Regent Street, Belize City, Belize','email'=>'acctsrec@brodies.bz','contact'=>'Mrs. Alice Riveroll','phone'=>'227-7070','mobile'=>null,'tax_id'=>'005057'],
            ['name'=>'Jenny Mira','account_no'=>'BBL 163524010140000','address'=>'#7 Otox-Ha Street, Belmopan','email'=>'mirajenny962@gmail.com','contact'=>'Jenny Mira','phone'=>'614-7688','mobile'=>null,'tax_id'=>'363915'],
            ['name'=>'Jorge Sajia','account_no'=>'BBL 110444010220001','address'=>'Church Street, Punta Gorda','email'=>'sajiajj@gobmail.gov.bz','contact'=>'Jorge Sajia','phone'=>'613-5250','mobile'=>null,'tax_id'=>'173033'],
            ['name'=>'Jovilee Apartment','account_no'=>'ABL 2110008594','address'=>'Corner Kelly and Nurse Seay Street','email'=>'jovilee.bz@gmail.com','contact'=>'Crystal Leiva-Mangar','phone'=>'610-8182','mobile'=>null,'tax_id'=>'HOT08691'],

            // ---------- Page 5 ----------
            ['name'=>'Judia Young','account_no'=>'ABL 211886716','address'=>'#9 Venezuela Street','email'=>'youngjudia@gmail.com','contact'=>'Judia Young','phone'=>'634-6470','mobile'=>null,'tax_id'=>'235982'],
            ['name'=>'Julian Cho Technical High School','account_no'=>'BBL 105747010120001','address'=>'14.5 Miles San Antonio Road, Toledo','email'=>'principaljctech@gmail.com','contact'=>'Mr. Elmar Requena','phone'=>'610-6623','mobile'=>'613-6625','tax_id'=>'43393'],
            ['name'=>'Juliet Wagner','account_no'=>'BBL 117002010220001','address'=>'7615 Canada Hill','email'=>'ladyjlb66@gmail.com','contact'=>'Juliet Wagner','phone'=>'615-1801','mobile'=>null,'tax_id'=>'0022163'],
            ['name'=>'Jungle Sea Ventures Limited/The Lodge at Jaguar Reef','account_no'=>'BBL 100273010120001','address'=>'100 Sittee River Road, False Sittee Point, Hopkins','email'=>'receivables@jaguarreef.com','contact'=>'Marlon Santos','phone'=>'822-3851','mobile'=>null,'tax_id'=>'000106'],
            ['name'=>'Juniors Construction Supplies','account_no'=>'BBL 170882010120001','address'=>'#51 George Price Street Punta Gorda,','email'=>'calvert_junior@hotmail.com','contact'=>'Calvert Supaul Jr','phone'=>'615-1059','mobile'=>null,'tax_id'=>'048924'],
            ['name'=>'KBH Security Systems & Services Ltd.','account_no'=>'BBL 132682010120001','address'=>'4 Woods Street PO BOX 815 Belize City','email'=>'billing@kbh.bz','contact'=>'Steve Good','phone'=>'227-2263','mobile'=>null,'tax_id'=>'000033'],
            ['name'=>'KD Productions','account_no'=>'BBL 174419010120026','address'=>'5 Hudson St. San Ignacio Town, Cayo','email'=>'lealcoser@gmail.com','contact'=>'Lucilo Alcoser or Luciano Alcoser','phone'=>'670-2927','mobile'=>'674-4489','tax_id'=>'223790'],
            ['name'=>'Kelsie & Kemuel','account_no'=>'ABL 211832454','address'=>'1386 Ministry Drive','email'=>'bookings@kelsieandkemuel.com','contact'=>'Kemuel Parham','phone'=>'6136798','mobile'=>null,'tax_id'=>'322003'],
            ['name'=>'Kenmars Bed and Breakfast','account_no'=>'BBL 119691010120001','address'=>'22/24 Halfmoon Ave., City of Belmopan, Belize, C.A.','email'=>'info@kenmar.bz','contact'=>'Karlin Fuller','phone'=>'822-0118','mobile'=>null,'tax_id'=>'100485'],
            ['name'=>"Khy's Kreamy Kreations",'account_no'=>'ABL 211977583','address'=>'21 Mex Avenue, Belize City','email'=>'aeishapollard16@hotmail.com','contact'=>'Aeisha Robinson','phone'=>'610-4042','mobile'=>'638-3617','tax_id'=>'172747'],
            ['name'=>'Kiefer Alvarez','account_no'=>'ABL 21200021589','address'=>'52 New Road, Belize City','email'=>'arkkga@gmail.com','contact'=>'Kiefer Alvarez','phone'=>'602-6401','mobile'=>null,'tax_id'=>'195144'],
            ['name'=>'Kieran Ryan','account_no'=>'BBL 229876010220025','address'=>'# 2 Earlies Street','email'=>'kryan@ub.edu.bz','contact'=>'Kieran Ryan','phone'=>'614-7120','mobile'=>null,'tax_id'=>'82115'],
            ['name'=>'Kimberly Lawrence (Del Charms Party Center)','account_no'=>'BBL 106503010220001','address'=>'Forest Home Village, Punta Gorda','email'=>'finance@trebelize.org','contact'=>'Charmini Coleman','phone'=>'673-2155','mobile'=>'665-5953','tax_id'=>'159148'],
            ['name'=>'Konchan Enterprises Ltd','account_no'=>'BBL 263672010120000','address'=>'Mile 3, Iguana Creek Road','email'=>'sales@belizeindustrial.com','contact'=>'Marlin Perez','phone'=>'670-0604','mobile'=>null,'tax_id'=>'351650'],
            ['name'=>'Koop Sheet Metal','account_no'=>'BBL 118086010120026','address'=>'Iguana Creek Road','email'=>'sales@koopsheet.com','contact'=>'Eddie Penner','phone'=>'823-0118','mobile'=>null,'tax_id'=>'155481'],
            ['name'=>"Koops' Tinsmith",'account_no'=>'BBL 129931010120001','address'=>'Spanish Lookout, Belize','email'=>'accounts@koopstinsmith.bz','contact'=>'Alvina Koop','phone'=>'823-0126','mobile'=>null,'tax_id'=>'005417'],
            ['name'=>'Kristen Williams','account_no'=>'ABL 2110061477','address'=>'#32 Fresh Pond, Belize','email'=>'kirstenwilliams830@gmail.com','contact'=>'Kristen Williams','phone'=>'601-3962','mobile'=>null,'tax_id'=>'248756'],
            ['name'=>'La Sante Pharmacy','account_no'=>'BBL 232391010120025','address'=>'Benque Viejo Road','email'=>'lasantepharmacy@icloud.com','contact'=>'Lidia Muzul','phone'=>'633-5791','mobile'=>null,'tax_id'=>'182765'],
            ['name'=>'LaDestina Travel','account_no'=>'ABL 2110021168','address'=>'#63 Liberty Avenue Orange Walk Town','email'=>'ladestinatravel@gmail.com','contact'=>'Nancy Gomez','phone'=>'322-2088','mobile'=>'615-2088','tax_id'=>'261614'],
            ['name'=>"Landy's Marine",'account_no'=>'ABL 211685559','address'=>'Orange Walk Town','email'=>'landysmarine@gmail.com','contact'=>'Lizbeth Meza','phone'=>'622-5997','mobile'=>null,'tax_id'=>'372207'],
            ['name'=>'Lasting Impressions Designs','account_no'=>'ABL 2110021041','address'=>'Santa Familia Street, Orange Walk Town','email'=>'lidbelize@gmail.com','contact'=>'Gracielle King','phone'=>'707-9026','mobile'=>null,'tax_id'=>'328044'],
            ['name'=>'LATech Solutions','account_no'=>'ABL 2110039739','address'=>'8 1/2 Mls George Price Highway','email'=>'latechsolutionsbz@gmail.com','contact'=>'Luis Torres','phone'=>'600-1934','mobile'=>null,'tax_id'=>'051267'],
            ['name'=>'Latitude 17 Tours','account_no'=>'ABL 211860912','address'=>'Northside Caye Caulker','email'=>'catillohugo8@gmail.com','contact'=>'Hugo Castillo','phone'=>'629-6163','mobile'=>null,'tax_id'=>'126256'],
            ['name'=>'Laughing Supermarket','account_no'=>'BBL 170572010120001','address'=>'40 West Street','email'=>'love-bakery@qq.com','contact'=>'Leo Yan','phone'=>'6288882','mobile'=>null,'tax_id'=>'210198'],
            ['name'=>'Lawrence Vernon','account_no'=>'BBL 133092010170025','address'=>'116 North Front Street, Belize City','email'=>'lawrencevernon37@hotmail.com','contact'=>'Lawrence Vernon','phone'=>'624-3279','mobile'=>null,'tax_id'=>'34160'],
            ['name'=>'LDF Repairs & Services','account_no'=>'HBL 9141076','address'=>'178 Western Pines','email'=>'ldfrepairs@live.com','contact'=>'Lennox Flowers','phone'=>'600-4278','mobile'=>null,'tax_id'=>'006388'],
            ['name'=>'LDM LAW','account_no'=>'BBL 246094010120000','address'=>'No. 28 St. Thomas Street, Belize City, BZE','email'=>'leslie.ldmlaw@gmail.com','contact'=>'Leslie Mendez','phone'=>'631-3013','mobile'=>null,'tax_id'=>'193287'],
            ['name'=>'Leyonel Jones T/A Contemporary Designs','account_no'=>'ABL 2110048767','address'=>'#3 Spain Street, Santa Elena Town Cayo','email'=>'info@contemporarydesigns.bz','contact'=>'Leyonel Jones','phone'=>'672-1476','mobile'=>null,'tax_id'=>'116212'],
            ['name'=>'Lianne Hoare','account_no'=>'BBL 252641010160000','address'=>'21 Venezuela St. Belmopan','email'=>'Liannenhoare@gmail.com','contact'=>'Lianne Hoare','phone'=>'295-5385','mobile'=>null,'tax_id'=>'337615'],
            ['name'=>'Linda Vista Lumber Yard','account_no'=>'BBL 155959010120001','address'=>'40th Street, Spanish Lookout','email'=>'accounts@lindavista.bz','contact'=>'Scott Varro','phone'=>'670-5859','mobile'=>null,'tax_id'=>'003992'],
            ['name'=>'Living Maya Experience','account_no'=>'BBL 23357501020025','address'=>'Big Falls Village','email'=>'livingmayaexperience@gmail.com','contact'=>'Anita Cal','phone'=>'627-7408','mobile'=>null,'tax_id'=>null],
            ['name'=>'Lucca Gastro PUB','account_no'=>'ABL 100294959','address'=>'1 Princess MArgaret Drive','email'=>'hourbargrill@gmail.com','contact'=>'Cantekin Kelat','phone'=>'632-6596','mobile'=>null,'tax_id'=>'230382'],
            ['name'=>'Lucia Coyoc','account_no'=>'BBL 211398010160025','address'=>'San Ignacio, Cayo','email'=>'lucycoyoc@yahoo.com','contact'=>'Lucia Coyoc','phone'=>'627-3109','mobile'=>null,'tax_id'=>'68255'],
            ['name'=>'Luis Alberto Garcia','account_no'=>'ABL 211325948','address'=>'Cristo Rey Village, Cayo','email'=>'gluis3037@gmail.com','contact'=>'Luis Garcia','phone'=>'624-5838','mobile'=>null,'tax_id'=>'021004'],
            ['name'=>'Luke Ramos','account_no'=>'BBL 117367010220001','address'=>'19 Pine Apple Street, Belmopan','email'=>'lukeramos@gmail.com','contact'=>'Luke Ramos','phone'=>'610-5928','mobile'=>null,'tax_id'=>'011182'],
            ['name'=>'M & M Distributors','account_no'=>'BBL 118110010120001','address'=>'#4 Lubaantun Street, Belmopan, Belize','email'=>'mnmdistributors17@gmail.com','contact'=>'Mrs. Yvette Mazariegos','phone'=>'607-4523','mobile'=>null,'tax_id'=>'123291'],
            ['name'=>'M &Y Shopping Center','account_no'=>'BBL 274875010120000','address'=>'03 Nicaragua Street','email'=>'steven471722217@gmail.com','contact'=>'QingYuan Tan (Steven)','phone'=>'634-4134','mobile'=>null,'tax_id'=>'212791'],
            ['name'=>'Maddjc Computer Services','account_no'=>'BBL 170728010120001','address'=>'Forest Home, Toledo','email'=>'cheldej@yahoo.com','contact'=>'Derick Borland','phone'=>'660-2238','mobile'=>null,'tax_id'=>'7913'],
            ['name'=>'Madison Home Decor Ltd.','account_no'=>'ABL 100310913','address'=>'1904 Constitution Drive Belmopan City','email'=>'madisonfurniturebz@gmail.com','contact'=>'Ashton Usher','phone'=>'674-4792','mobile'=>'674-1912','tax_id'=>'245287'],
            ['name'=>'Magdalene Zuniga','account_no'=>'ABL 2120003992','address'=>'Maya Mopan, Belmopan','email'=>'karen.wade18@yahoo.com','contact'=>'Magdalene Zuniga','phone'=>'615-3472','mobile'=>null,'tax_id'=>'366497'],
            ['name'=>"Manza's Brokerage Service",'account_no'=>'ABL 210628329','address'=>'Benque Viejo Border Cayo District','email'=>'polo.manza@yahoo.com','contact'=>'Policarpo Manzanero','phone'=>'607-7443','mobile'=>null,'tax_id'=>'219585'],
            ['name'=>"Mar's Patio Rentals",'account_no'=>'BBL 246886010220000','address'=>'1268 Sundial Avenue, Coral Grove','email'=>'gmatus001@yahoo.com','contact'=>'Gustavo Matus','phone'=>'223-6636','mobile'=>'610-1909','tax_id'=>'002560'],
            ['name'=>'Maria Banner','account_no'=>'BBL 215193010160025','address'=>'Camalote Village','email'=>'mariabanner929@gmail.com','contact'=>'Maria Banner','phone'=>'6136531','mobile'=>null,'tax_id'=>'117441'],
            ['name'=>'Maria Chang','account_no'=>'HBL 1266693','address'=>'Cleghorn Street, Belize City, Belize','email'=>'robertmatchang_210@hotmail.com','contact'=>'Robert Chang','phone'=>'615-7409','mobile'=>null,'tax_id'=>'5158'],
            ['name'=>'Marina Francis','account_no'=>'BBL 134753010160001','address'=>'Pluto Street, Hillview, Santa Elena, Cayo','email'=>'marinafrancis408@gmail.com','contact'=>'Marina Francis','phone'=>'612-4800','mobile'=>'625-1645','tax_id'=>'008878'],
            ['name'=>'Marine Technical Services','account_no'=>'BBL 246271010120000','address'=>'118 North Front Street','email'=>'bzeseadog@yahoo.com','contact'=>'Sidney Thurton','phone'=>'670-7093','mobile'=>null,'tax_id'=>'2846'],
            ['name'=>'Marlon Martin','account_no'=>'NCB 354707284','address'=>'32 University Close, College Common, UWI Mona, Kingston','email'=>'marlon.martinam@gmail.com','contact'=>'Marlon Martin','phone'=>'18768921312','mobile'=>null,'tax_id'=>'111926394'],
            ['name'=>'Matus Medical Supplies','account_no'=>'HBL 1718','address'=>'No. 6 St. Thomas Street, Belize City, Belize','email'=>'matusmedical@yahoo.com','contact'=>'Mario Avela/Kelsey Carvajal','phone'=>'611-0190','mobile'=>'615-2028','tax_id'=>'747582'],
            ['name'=>'Maya Island Air','account_no'=>'BBL 135954010120002','address'=>"Sir Barry Bowen Municipal Airport, St. Matthew's Street, Belize City",'email'=>'ardept@mayaislandair.com','contact'=>'Anilese Pech','phone'=>'223-1403','mobile'=>'615-3064','tax_id'=>'84733'],
            ['name'=>'MC Velocity Graphix','account_no'=>'BBL 251776010120000','address'=>'Santa Elena Street, Orange Walk Town','email'=>'velocitygraphix418@gmail.com','contact'=>'Jose Casanova','phone'=>'662-0751','mobile'=>null,'tax_id'=>'342275'],
            ['name'=>"McFadzean's Tours and Charters",'account_no'=>'BBL 281110010120000','address'=>'Burrell Boom Village, Mussle Creek Road','email'=>'info@mcfadzeanscharters.bz','contact'=>'Mahlon McFadzean','phone'=>'615-2449','mobile'=>null,'tax_id'=>'343328'],
            ['name'=>'Medicare Plus Pharmacy','account_no'=>'HBL 1021-0861','address'=>'Main Street, Market Square, Belmopan','email'=>'medicareplusrx@gmail.com','contact'=>'Joelyn Poornananda/Karen Martinez','phone'=>'615-4529','mobile'=>null,'tax_id'=>'178684'],
            ['name'=>'Megs Corner Cafe','account_no'=>'ABL 100319754','address'=>'1 South Street, Belize City, Belize','email'=>'manager@megscorner.cafe','contact'=>'Judith Munoz','phone'=>'227-1114','mobile'=>'610-1101','tax_id'=>'239750'],
            ['name'=>'Melvin Nunez','account_no'=>'ABL 2120005562','address'=>'2 1/2 Miles George Price Hwy, Belize City','email'=>'melvinalexnunezz03@gmail.com','contact'=>'Melvin Nunez','phone'=>'609-6180','mobile'=>null,'tax_id'=>'330527'],
            ['name'=>'MIa Wagner','account_no'=>'BBL 178898010220000','address'=>'Cattle Landing','email'=>'miavwagner123@gmail.com','contact'=>'Mia Wagner','phone'=>'605-9285','mobile'=>null,'tax_id'=>'000662'],

            // ---------- Page 6 ----------
            ['name'=>'Midwest Steel & Agro Supplies','account_no'=>'BBL 132850010120001','address'=>'Center Avenue,Spanish Lookout, Cayo','email'=>'accounts@midweststeel.bz','contact'=>'Abe Thiessen','phone'=>'823-0131','mobile'=>null,'tax_id'=>'15480'],
            ['name'=>'Mikado Store','account_no'=>'BBL 159485010120001','address'=>'37 Albert Street Belize City Belize','email'=>'mikadostore@gmail.com','contact'=>'Desiree Lino','phone'=>'227-2981','mobile'=>null,'tax_id'=>'21854'],
            ['name'=>'Mirador Hotel','account_no'=>'BBL 16344501012001','address'=>'4th Avenue and 1st Avenue, Corozal Town','email'=>'miradorhotelcoro@hotmail.com','contact'=>'Karina Ake','phone'=>'422-0189','mobile'=>null,'tax_id'=>'341902'],
            ['name'=>'Molina Industrial Solutions Ltd.','account_no'=>'BBL 232310010120025','address'=>'Mile 54.5 Hummingbird Highway,','email'=>'misl@misl.bz','contact'=>'Fernanda Molina','phone'=>'610-0919','mobile'=>'613-8277','tax_id'=>'244570'],
            ['name'=>'Monkey Bay Wildlife Sanctuary.','account_no'=>'ABL 2110027932','address'=>'Mile 31.5 George Price Highway, Cayo District','email'=>'info@monkeybaybelize.com','contact'=>'Michelle Guzman','phone'=>'832-7734','mobile'=>null,'tax_id'=>'199960'],
            ['name'=>'Mopan Technical High School','account_no'=>'BBL 121992010120001','address'=>'Benque Viejo del Carmen Town, Cayo District','email'=>'principal@mths.edu.bz','contact'=>'Katie Jones','phone'=>'823-2028','mobile'=>null,'tax_id'=>'16114'],
            ['name'=>'Morales Wood Works','account_no'=>'BBL 142221010220000','address'=>"Bradley's Bank Area Santa Elena Town, Cayo",'email'=>'elvismor93@gmail.com','contact'=>'Elvis Morles','phone'=>'602-1318','mobile'=>null,'tax_id'=>'367041'],
            ['name'=>'MPG Design Studio','account_no'=>'BBL 219442010160025','address'=>'14 Zericote Street, Belize City, Belize District','email'=>'miles.p.luna@gmail.com','contact'=>'Miles Geban','phone'=>'620-9047','mobile'=>null,'tax_id'=>'188725'],
            ['name'=>'Nelson-Jameson, Inc','account_no'=>'BMO 0045898402','address'=>'3200 S. Central Ave., Marshfield, WI 54449','email'=>'globalsales@nelsonjameson.com','contact'=>'Lon Krause','phone'=>'715-387-1151','mobile'=>null,'tax_id'=>'39-0987938'],
            ['name'=>'Nova Ink Creations','account_no'=>'BBL 221037010160000','address'=>'Cristo Rey, Corozal','email'=>'novainkcreations501@gmail.com','contact'=>'Irai Leonel Ruiz','phone'=>'676-6612','mobile'=>null,'tax_id'=>'252729'],
            ['name'=>'Nuru & the Garifuna Beats','account_no'=>'BBL 159575010220001','address'=>'23 Ghans Ave','email'=>'dayaanellis@gmail.com','contact'=>'Dayaan Ellis','phone'=>'612-1310','mobile'=>null,'tax_id'=>'330477'],
            ['name'=>'Omnistudio Branding & Design','account_no'=>'ABL 2110079618','address'=>'6 Mamie Apple, Belmopan','email'=>'virgilio@omnistudio.bz','contact'=>'Virgilio Cus','phone'=>'6115121','mobile'=>null,'tax_id'=>'259275'],
            ['name'=>'OTillet Bus Line','account_no'=>'BBL 261612010120000','address'=>'Palmar Boundry Road','email'=>'marieltersi@gmail.com','contact'=>'Omar Tillett Sr.','phone'=>'613-5180','mobile'=>null,'tax_id'=>'226580'],
            ['name'=>'Our Community Pharmacy','account_no'=>'BBL 246111010120000','address'=>'Our Community Pharmacy, Nim Li Punit Street, The Emporium Plaza Suite 112','email'=>'mmadrid@ocpharma.org','contact'=>'Magdalena Madrid','phone'=>'621-0121','mobile'=>null,'tax_id'=>'332759'],
            ['name'=>'Pearleen Coleman','account_no'=>'BBL 102612010220001','address'=>'Big Falls Village','email'=>null,'contact'=>'Pearleen Coleman','phone'=>'630-4069','mobile'=>null,'tax_id'=>'246128'],
            ['name'=>"Plett's Home Builders",'account_no'=>'BBL 233735010120025','address'=>'West 40th St. Spanish Lookout, Cayo District','email'=>'sales@plettshomebuilders.com','contact'=>'Denise Smith','phone'=>'666-0398','mobile'=>'603-2032','tax_id'=>'1746'],
            ['name'=>'Premier Charters & Tours Ltd.','account_no'=>'ABL 100152692','address'=>'2.5 Miles Phillip Goldson Highway Belize City','email'=>'premiercharters@gmail.com','contact'=>'Kaydi Batty','phone'=>'223-5559','mobile'=>null,'tax_id'=>'105713'],
            ['name'=>'Premium Wines & Spirits','account_no'=>'BBL 262551010120000','address'=>'166 Newtown Barracks, Belize City','email'=>'orders@premiumwines.bz','contact'=>'Celeny Flores','phone'=>'2234984','mobile'=>'606-1548','tax_id'=>'348354'],
            ['name'=>'Price & Company Ltd.','account_no'=>'ABL 100218426','address'=>'8 Daly Street Belize City, Belize','email'=>'frontdesk@priceandcompany.com.bz','contact'=>'Kathy Esquivel','phone'=>'223-5239','mobile'=>null,'tax_id'=>'5031'],
            ['name'=>'PRINT BiLLIEVE','account_no'=>'BBL 249316010120000','address'=>'Roaring Creek, Another World','email'=>'printbellieve@gmail.com','contact'=>'Vaughn King','phone'=>'613-5156','mobile'=>null,'tax_id'=>'289450'],
            ['name'=>'Pro Auto Store & Services','account_no'=>'BBL 272669010120000','address'=>'Pomona Village, S/C Valley Rd','email'=>'proautobz@gmail.com','contact'=>'Susana Chavaria','phone'=>'612-1234','mobile'=>null,'tax_id'=>'348719'],
            ['name'=>'Pro Marketing Agency Ltd.','account_no'=>'ABL 100318149','address'=>'32 Mayflower Street, Belmopan, Cayo','email'=>'sales@promarketing.bz','contact'=>'Jose Aguilar','phone'=>'614-0813','mobile'=>null,'tax_id'=>'155481'],
            ['name'=>'Prosser Fertilizer & Agrotec Co. Ltd.','account_no'=>'BBL 129843010120027','address'=>'Mile 8 George Price Highway, Belize District','email'=>'admin@prosserbelize.com','contact'=>'Delsia Pate','phone'=>'223-5410','mobile'=>null,'tax_id'=>'5115'],
            ['name'=>'Pumps & Motor of Belize LTD.','account_no'=>'ABL 2110054140','address'=>'3.5 Miles, Phillips Goldson Highway, Belize City','email'=>'mel.auil@pumpsandmotors.bz','contact'=>'Jorge Auil','phone'=>'223-6687','mobile'=>'223-7625','tax_id'=>'178599'],
            ['name'=>'Quality Poultry Products P.G.','account_no'=>'BBL 232419010120026','address'=>'Jose Maria Nunez Street, Punta Gorda','email'=>'harvey@qualitypoultryproducts.com','contact'=>'Harvey Miranda','phone'=>'625-1198','mobile'=>null,'tax_id'=>'15512'],
            ['name'=>'Quality Poultry Products (Bmp)','account_no'=>'HBL 1344775','address'=>'#107 Hummingbird Highway','email'=>'nelson@qualitypoultryproducts.com','contact'=>'Nelson Valladares','phone'=>'625-1010','mobile'=>null,'tax_id'=>'15512'],
            ['name'=>'Quality Poultry Products SPL','account_no'=>'HBL 1344775','address'=>'Center Road, Spanish Lookout','email'=>'info@qualitypoultryproducts.com','contact'=>'Adai Vasquez','phone'=>'823-0113','mobile'=>null,'tax_id'=>'15512'],
            ['name'=>'R.S.V. Limited','account_no'=>'ABL 100029941','address'=>'7145 Slaughterhouse Road, Belize City','email'=>'rsvreceivables@gmail.com','contact'=>'Tanya Humes','phone'=>'223-0246','mobile'=>null,'tax_id'=>'005374'],
            ['name'=>'Rahjeme Coleman','account_no'=>'ABL 2120031322','address'=>'#5 Dove St. Maya Mopan, Belmopany','email'=>'rahjemecoleman12@yahoo.com','contact'=>'Rahjeme Coleman','phone'=>'609-4786','mobile'=>null,'tax_id'=>'241335'],
            ['name'=>'Randy Gladstone Joseph Jr.','account_no'=>'BBL 101846010220001','address'=>'10 McFadzean Street, Belmopan, Cayo','email'=>'randygjosephjr85@gmail.com','contact'=>'Randy Joseph Jr.','phone'=>'675-7265','mobile'=>null,'tax_id'=>'168515'],
            ['name'=>"Reimer's Feed Mill - SPL",'account_no'=>'ABL 100111790','address'=>'Center Road, Spanish Lookout, Cayo','email'=>'accts_rec@reimersfeed.com','contact'=>'Mildre Orellana','phone'=>'613-0856','mobile'=>null,'tax_id'=>'119'],
            ['name'=>"Reimers' Service Station",'account_no'=>'BBL 129848010120001','address'=>'Center Avenue, Spanish Lookout','email'=>'accounts@rscbelize.com','contact'=>'Jessica Kornelsen','phone'=>'823-0122','mobile'=>'621-0602','tax_id'=>'162641'],
            ['name'=>'Respondus Inc','account_no'=>'JP Morgan 357508651','address'=>'PO Box 3247, Redmond, WA 98073 USA','email'=>'ar@respondus.com','contact'=>'Kelsey Kilcoyne','phone'=>'425-497-0389','mobile'=>null,'tax_id'=>'91-2050620'],
            ['name'=>'RETO .S.A.','account_no'=>'Banco Promerica 3a calle 1-76 Zona 3n Bocs del Monte, Villa Canales','address'=>null,'email'=>'jboy@actxlabel','contact'=>'Jos Luis Boy','phone'=>'33100','mobile'=>null,'tax_id'=>'8406901'],
            ['name'=>'Revrobotics.com','account_no'=>'WFB 7170694827','address'=>'2941 Commodore Dr, Suite 110, Carollton, TX 75007','email'=>'sales@revrobotics.com','contact'=>'Janee','phone'=>'844-255-2267','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>'RF&G Insurance Co. Ltd.','account_no'=>'BB 135871010120001','address'=>'One Coney Drive, PO Box 661, Belize City','email'=>'jespejo@rfginsurancebelize.com','contact'=>'Jorge Espejo/Kevin E. Franklin','phone'=>'223-5734','mobile'=>'610-0618','tax_id'=>'106373'],
            ['name'=>'Richard Smith/D Deal','account_no'=>'BBL 246861010120000','address'=>'Roaring Creek, Cayo','email'=>'richardsmithbmp@gmail.com','contact'=>'Richard Smith','phone'=>'615-0710','mobile'=>null,'tax_id'=>'034479'],
            ['name'=>'Roaring River Golf & accommodations','account_no'=>'BBL 191205010120000','address'=>'Roaring Creek Village','email'=>'roaringriveroffice@gmail.com','contact'=>'Julianni Chan','phone'=>'613-4655','mobile'=>null,'tax_id'=>'335283'],
            ['name'=>'Robert Edwin Alfaro','account_no'=>'BB 121284010110008','address'=>'Armando Sabido Drive, San Ignacio','email'=>'dingfarobrokerage@gmail.com','contact'=>'Robert Alfaro','phone'=>'610-3175','mobile'=>null,'tax_id'=>'009575'],
            ['name'=>'Ronaldo Coc (DJ RJ7)','account_no'=>'BBL 268039010160000','address'=>'Belmopan City, Cayo','email'=>'djrj7.bz@gmail.com','contact'=>'Ronaldo Coc','phone'=>'632-5137','mobile'=>null,'tax_id'=>'363763'],
            ['name'=>'Rosalind Terry','account_no'=>'BBL 162738010220001','address'=>'7th Street, Orange Walk Town','email'=>'lillyrt122@gmail.com','contact'=>'Rosalind Terry','phone'=>'605-5716','mobile'=>null,'tax_id'=>'027949'],
            ['name'=>'Roshawn Garbutt','account_no'=>'HBL 9217796','address'=>'West Street, Punta Gorda','email'=>'Roshawngarbutt157@gmail.com','contact'=>'Roshawn Garbutt','phone'=>'600-7081','mobile'=>null,'tax_id'=>null],
            ['name'=>'Royal Catering Services','account_no'=>'BBL 135953010120001','address'=>'8464 Gordon Street Belize City Belize','email'=>'royalcateringservices@hotmail.com','contact'=>'Gwendolyn Sutherland','phone'=>'222-5073','mobile'=>'605-3620','tax_id'=>'48282'],
            ['name'=>'Royal View Apartments Plus','account_no'=>'BBL 245785010120000','address'=>'4.5 Miles George Price Hiighway Belize City','email'=>'booking@royalview.bz','contact'=>'Lloyd Sutherland','phone'=>'610-0717','mobile'=>null,'tax_id'=>'48282'],
            ['name'=>'Rozan Mesh (Young Farmers Store)','account_no'=>'HBL 4141198','address'=>'Bullett Tree Road, San Ignacio, Cayo','email'=>'Youngfarmerstore@gmail.com','contact'=>'Cesar Mesh','phone'=>'654-7560','mobile'=>'674-8064','tax_id'=>'340206'],
            ['name'=>"RVJ's Creative Design",'account_no'=>'BBL 144172010120001','address'=>'Punta Gorda Town','email'=>'vrrjacobs@yahoo.com','contact'=>'Radiance Jacobs','phone'=>'722-2577','mobile'=>'607-1410','tax_id'=>'55889'],
            ['name'=>'S&L Travel and Tours','account_no'=>'BBL 133896010120000','address'=>'69 West Collet Canal','email'=>'info@sltravelbelize.com','contact'=>'Elvira Nuñez','phone'=>'610-1384','mobile'=>null,'tax_id'=>'64'],
            ['name'=>'Sacred Heart College','account_no'=>null,'address'=>'1 Joseph Andrews Drive','email'=>'angie.escarraga@shc.edu.bz','contact'=>'Angie Escarraga','phone'=>'824-2102','mobile'=>null,'tax_id'=>'15402'],
            ['name'=>'Sai Outlet','account_no'=>'ABL 2110046375','address'=>'36 Queen Street','email'=>'saioutlet.bz@gmail.com','contact'=>'Vikky Daswani','phone'=>'622-4749','mobile'=>'666-7894','tax_id'=>'346565'],
            ['name'=>'Salvador W. Habet Ltd.','account_no'=>'BBL 139029010120002','address'=>'34 Regent Street, Belize City,','email'=>'sales@swhabet.com','contact'=>'Alyssa Habet','phone'=>'227-7066','mobile'=>'227-7067','tax_id'=>'3'],
            ['name'=>'San Ignacio & Santa Elena Basketball Assn','account_no'=>'BBL 165083010120001','address'=>'#47 Fourth Street, San Ignacio Town,Cayo District','email'=>'ssbabasketball@gmail.com','contact'=>'Karim Juan','phone'=>'614-1435','mobile'=>null,'tax_id'=>'036338'],
            ['name'=>'San Ignacio Resort Hotel','account_no'=>'BBL 121149010120002','address'=>'18 Buena Vista Street, San Ignacio, Belize','email'=>'accountc@sanignaciobelize.com','contact'=>'Rachel Salazar','phone'=>'824-2034','mobile'=>null,'tax_id'=>'5214'],
            ['name'=>'San Pedro Belize Water Taxi Ltd.','account_no'=>'ABL 100183052','address'=>'111 North Front Street, Belize City','email'=>'receivables@belizewatertaxi.com','contact'=>'Rocio Madrid','phone'=>'223-2225','mobile'=>null,'tax_id'=>'149551'],
            ['name'=>'Sandra Mai','account_no'=>'ABL 211102894','address'=>'Trial Farm Village, Orange Walk','email'=>'blendbelize501@gmail.com','contact'=>'Sandra Mai','phone'=>'634-9763','mobile'=>null,'tax_id'=>'225261'],
            ['name'=>'Sansco Supermarket','account_no'=>'BBL 192340010120000','address'=>'Santa Elena, Cayo','email'=>'704879919zhi@gmail.com','contact'=>'Kevin Liang','phone'=>'632-3237','mobile'=>null,'tax_id'=>'242901'],

            // ---------- Page 7 ----------
            ['name'=>'ShaBla Events','account_no'=>'BBL 240084010120000','address'=>'#1 Pineapple Street, Santa Elena Cayo','email'=>'shablaevents@gmail.com','contact'=>'Shalve Butcher','phone'=>'670-3421','mobile'=>null,'tax_id'=>'4360'],
            ['name'=>'Shanice Garbutt','account_no'=>'ABL 211452667','address'=>'Belize City','email'=>'garbuttshanice1996@gmail.com','contact'=>'Shanice Garbutt','phone'=>'635-8880','mobile'=>null,'tax_id'=>'197517'],
            ['name'=>'Shannon Bucknor','account_no'=>'BBL 117042010220001','address'=>'6/8 Ambergris Avenue Belmopan, Belize C.A','email'=>'shannonyvettebucknor@gmail.com','contact'=>'Shannon Bucknor','phone'=>'608-3777','mobile'=>'614-3028','tax_id'=>'12659'],
            ['name'=>'Shawn Mahler','account_no'=>'ABL 211408225','address'=>'12 Lizarraga Avenue, Belize City','email'=>'shmahler@smahlerca-cisa.com','contact'=>'Shawn Mahler','phone'=>'610-0465','mobile'=>null,'tax_id'=>'33721'],
            ['name'=>'Sigertronic Systems','account_no'=>'BBL 232432010120025','address'=>'6 Pickstock Street, Belize City.','email'=>'sales@sigertronic.com','contact'=>'Sasha Waite','phone'=>'223-3600','mobile'=>'610-3600','tax_id'=>'150171'],
            ['name'=>'Simon Quan & Co Ltd','account_no'=>'ABL 2110054793','address'=>'16 & 24 Queen Street, Belize City','email'=>'info.simonquan@protonmail.com','contact'=>'Simon Quan','phone'=>'223-4124','mobile'=>'223-0271','tax_id'=>'511499'],
            ['name'=>'Sitnah Blease','account_no'=>'ABL 211646637','address'=>'Sandhill Village','email'=>'bleasesitnah@gmail.com','contact'=>'Sitnah Blease','phone'=>'613-6372','mobile'=>null,'tax_id'=>'202552'],
            ['name'=>'Slingshot Avertising & Signs','account_no'=>'BBL 233895010120025','address'=>'48 Baymen Avenue Belize','email'=>'slingshotads@gmail.com','contact'=>'Melissa Espat','phone'=>'223-6348','mobile'=>'610-5533','tax_id'=>'365493'],
            ['name'=>'SMART','account_no'=>'BBL 135756010120002','address'=>'2 1/2 Philip Goldson Highway','email'=>'billing@speednet-wireless.com','contact'=>'Stephanie Cansino/Grace Godfrey','phone'=>'280-2134','mobile'=>'280-2157','tax_id'=>'107926'],
            ['name'=>'SOL Belize Limited','account_no'=>'BBL 163209010120001','address'=>'2.5 Miles, Phillip Goldson Highway Belize City, Belize','email'=>'Finance_Belize@solpetroleum.com','contact'=>'Leandro Osgalla/Shahira Quiroz','phone'=>'223-0406','mobile'=>'610-2446','tax_id'=>'000026'],
            ['name'=>'Spanish Lookout Volleyball Academy','account_no'=>'BBL 211512010220025','address'=>'Spanish lookout','email'=>'coolbreeze8010@gmail.com','contact'=>'Jeffrey dueck','phone'=>'6728010','mobile'=>null,'tax_id'=>null],
            ['name'=>'Special Occasions Belize (Sandra Jones)','account_no'=>'BBL 273457010120000','address'=>'Belmopan City, Cayo','email'=>'sandraprjones04@gmail.com','contact'=>'Sandra Jones','phone'=>'614-5128','mobile'=>null,'tax_id'=>null],
            ['name'=>'Spindrift Hotel','account_no'=>'BBL 126986010120001','address'=>'984 Barrier Reef Dr San Pedro Belize','email'=>'spindrifthotel@yahoo.com','contact'=>'Yamileth Silva','phone'=>'601-8977','mobile'=>null,'tax_id'=>'005377'],
            ['name'=>'St. Charles Inn','account_no'=>'BBL 156235010120001','address'=>'23 King St. Punta Gorda','email'=>'saintcharlesinn1934@gmail.com','contact'=>null,'phone'=>'635-9492','mobile'=>null,'tax_id'=>'001866'],
            ['name'=>'Stamps-R-Us Belize','account_no'=>'ABL 2110068362','address'=>'#8 Caladium Street, Belmopan','email'=>'rjcrammond@gmail.com','contact'=>'Robin Crammond','phone'=>'605-0639','mobile'=>'616-3771','tax_id'=>'104075'],
            ['name'=>'Studio 7','account_no'=>'BBL 149384010120001','address'=>'53 Burns Avenue, San Ignacio Cayo','email'=>'Info@studio7bz.com','contact'=>'Shirley Jeal','phone'=>'610-6393','mobile'=>'610-6393','tax_id'=>'198039'],
            ['name'=>'Studio A','account_no'=>'BBL 242402010120000','address'=>'71 Orange Street, Belmopan, Cayo','email'=>'studioabelize@gmail.com','contact'=>'Karissa Alvarez','phone'=>'635-2202','mobile'=>null,'tax_id'=>'237936'],
            ['name'=>'Sun Rental Enterprise Ltd.','account_no'=>'ABL 100295663','address'=>'1154 Sunrise Avenue, Belize City, Belize District','email'=>'sunenterprise@live.com','contact'=>'Jeonae Perez','phone'=>'612-7368','mobile'=>null,'tax_id'=>'230947'],
            ['name'=>'Sunny City Supermarket','account_no'=>'BBL 164707010120001','address'=>'3883 Moutain view Blvd, Belmopan','email'=>'sunnycitysm@gmail.com','contact'=>'Amy Tan','phone'=>'822-0572','mobile'=>'630-8518','tax_id'=>'015633'],
            ['name'=>'Supreme Meats','account_no'=>'ABL 100278879','address'=>'116 Cemetery Road, Belize City, Belize','email'=>'suprememeats64@gmail.com','contact'=>'Avis Hoy','phone'=>'227-0344','mobile'=>'675-4756','tax_id'=>'041645'],
            ['name'=>'Sylvyn Trumbach','account_no'=>'ABL 200234672','address'=>'70 Freetown Rd. Belize City, Belize','email'=>'strumbach@hotmail.com','contact'=>'Sylvyn Trumbach','phone'=>'607-0916','mobile'=>null,'tax_id'=>'216972'],
            ['name'=>'Tadeo Bennett','account_no'=>'BBL 192760010220000','address'=>'Belmopan City, Cayo','email'=>'tadeos.bennett@gmail.com','contact'=>'Tadeo Bennett','phone'=>'625-4777','mobile'=>null,'tax_id'=>'355756'],
            ['name'=>'Taurus Blocks & Building Supplies','account_no'=>'ABL 100271171','address'=>'Branch Mouth Road San Ignacio Town, Cayo District, Belize C.A','email'=>'taurus_sand@yahoo.com','contact'=>'Mirian Castillo','phone'=>'666-8949','mobile'=>'666-8949','tax_id'=>'214981'],
            ['name'=>"Ter's Delight",'account_no'=>'BBL 211238010160025','address'=>'26 Stann Creek Street, Belmopan City','email'=>'tersdelites52@gmail.com','contact'=>'Teresita Chan','phone'=>'620-7406','mobile'=>'611-6724','tax_id'=>'261324'],
            ['name'=>"Teul's Grocery Store",'account_no'=>'BBL 156440010120001','address'=>'Lucille Melendez Blvd','email'=>'zitateul7@gmail.com','contact'=>'Zita Teul','phone'=>'611-4876','mobile'=>null,'tax_id'=>'341194'],
            ['name'=>'The Angelus Press','account_no'=>'BBL 117472010120001','address'=>'The Angelus Press Limited','email'=>'rcoleman@santiagocastilloltd.com','contact'=>'Deborah Gomez','phone'=>'609-3895','mobile'=>'615-1041','tax_id'=>'5122'],
            ['name'=>'The Ansen Place','account_no'=>'ABL 100310833','address'=>'13 Blue Hole, Belmopan City, Cayo','email'=>'theansenplace@gmail.com','contact'=>'Darlin Ico','phone'=>'6154600','mobile'=>null,'tax_id'=>'165450'],
            ['name'=>'The Belize Zoo &Tropical Education Center','account_no'=>'ABL 2110018349','address'=>'Mile 29 George Price Highway, Belize District','email'=>'tec@belizezoo.org','contact'=>'Diana Perez','phone'=>'613-1832','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>'The Framing Shop','account_no'=>'BBL 232445010120025','address'=>'86 Cleghorn Street, Belize City','email'=>'framingshopbz@gmail.com','contact'=>'Ralph E Ramsey','phone'=>'600-6933','mobile'=>null,'tax_id'=>'2216'],
            ['name'=>'The Garage Restaurant & Chill Spot','account_no'=>'AB 2110021363','address'=>'11074 Butte Rows Rd, Belize City','email'=>'aurarose33@gmail.com','contact'=>'Mitzi Guy','phone'=>'614-3018','mobile'=>null,'tax_id'=>'341981'],
            ['name'=>'The Garifuna Collective','account_no'=>'BBL 233981010120025','address'=>'35 Elizabeth St. Benque Viejo Town','email'=>'obgsaudiogroup@gmail.com','contact'=>'Al Ovando','phone'=>'662-6249','mobile'=>null,'tax_id'=>'360110'],
            ['name'=>'The Inn At Twin Palms','account_no'=>'ABL 2110031699','address'=>'#5 Iliana Drive, Belmopan','email'=>'brianna0smith@yahoo.com','contact'=>'Brianna Hernandez','phone'=>'606-6719','mobile'=>null,'tax_id'=>'18577913'],
            ['name'=>'The Pest Control','account_no'=>'BBL 164659010260000','address'=>'21 Panama Ext. Belmopan','email'=>'pestfree87@gmail.com','contact'=>'Jazmine Ramos','phone'=>'610-8378','mobile'=>'610-8417','tax_id'=>'68676'],
            ['name'=>'The Reporter Press','account_no'=>'ABL 100313251','address'=>'147 West and Allenby Street, Belize City, Belize','email'=>'rodolfocastro53@yahoo.com','contact'=>'Rodolfo Castro','phone'=>'620-2266','mobile'=>null,'tax_id'=>'000121'],
            ['name'=>'The Seed Agent & Agro Supplies','account_no'=>'BBL 156651010120001','address'=>'Benque Viego Del Carmen','email'=>'amirpulido@yahoo.com','contact'=>'Amir Pulido','phone'=>'671-7600','mobile'=>null,'tax_id'=>'187812'],
            ['name'=>'The Stationery House LTD.','account_no'=>'ABL 100075191','address'=>'Mile 2-3/4 George Price Highway, Belize City','email'=>'lgabb@stahousedepot.com','contact'=>'Lurie Gabb','phone'=>'222-4070','mobile'=>'222-4071','tax_id'=>'190202'],
            ['name'=>'The Trophy Depot','account_no'=>'BBL 180994010120000','address'=>'Benque Viejo Street','email'=>'thetrophydepot@gmail.com','contact'=>'Gia Cal','phone'=>'611-3538','mobile'=>null,'tax_id'=>'149129'],
            ['name'=>"Theresita Mendoza (Mendoza's Kitchen)",'account_no'=>'BBL 286513010120000','address'=>'Roaring Creek Village','email'=>'mendozateresita@gmail.com','contact'=>'Theresita Mendoza','phone'=>'635-8045','mobile'=>null,'tax_id'=>'380135'],
            ['name'=>'Thunderbolt Water Taxi','account_no'=>'BBL 164799010120001','address'=>'Corozal Town, Corozal','email'=>'thunderbolttravels@yahoo.com','contact'=>'Danielle Rivero','phone'=>'628-8590','mobile'=>null,'tax_id'=>'205824'],
            ['name'=>'TIDE','account_no'=>'BBL 131297010120002','address'=>'1 Mile San Antonio Rd. Punta Gorda Town, Toledo','email'=>'info@tidebelize.org','contact'=>'Stephene Supaul','phone'=>'722-2274','mobile'=>'722-2655','tax_id'=>'016687'],
            ['name'=>'Tide Tours','account_no'=>'BBL 131297010120003','address'=>'1 Mile San Antonio Rd , Hopeville , Punta Gorda','email'=>'tidetours@tidebelize.org','contact'=>'Maureen Assi','phone'=>'671-2129','mobile'=>null,'tax_id'=>'82933'],
            ['name'=>'Tipsy Tuna Seaside & Sports Bar','account_no'=>'ABL 2120006077','address'=>'Placencia Village','email'=>'dani.ayaalaantonia@gmail.com','contact'=>'Danielle Ayala','phone'=>'635-8510','mobile'=>null,'tax_id'=>'257722'],
            ['name'=>'Toledo Community College','account_no'=>'BBL 131513010120001','address'=>'New City Area','email'=>'bursar.tcc@yahoo.com','contact'=>'Zalika Garbutt','phone'=>'628-6788','mobile'=>null,'tax_id'=>'016109'],
            ['name'=>'Toledo Exposure Documentary Films','account_no'=>'BBL 234005010120025','address'=>'Cattle Landing Village, Toledo','email'=>'toledoexposure_pgtv@hotmail.com','contact'=>'William Maheia','phone'=>'610-0978','mobile'=>'671-4422','tax_id'=>'4554'],
            ['name'=>'Toledo Farm Supply','account_no'=>'BBL 163236010120001','address'=>'53 George Price Street Punta Gorda, Toledo','email'=>'toledofarmsupply@gmail.com','contact'=>'Elvis Cho','phone'=>'722-2344','mobile'=>'614-6522','tax_id'=>'152931'],
            ['name'=>'Toledo Stones/ Russell','account_no'=>'BBL 270649010120000','address'=>'New Road , Punta Gorda','email'=>'gomeznathaniel599@gmail.com','contact'=>'Nathaniel Gomez','phone'=>'615-0634','mobile'=>null,'tax_id'=>'326170'],
            ['name'=>'Total Business Solutions Ltd.','account_no'=>'BBL 137713010120026','address'=>'21 Mango Street, Belmopan','email'=>'customerservice@tbsl.bz','contact'=>'Brenna Ramos','phone'=>'822-1800','mobile'=>'605-4633','tax_id'=>'138261'],
            ['name'=>'Total Marketing & Distrbution','account_no'=>'ABL 100263153','address'=>'Cor Freetown Rd & Cleghorn Street','email'=>'ivanna@totalmarketing.bz','contact'=>'Ivanna Villanueva','phone'=>'223-5560','mobile'=>'223-2202','tax_id'=>'1057848'],
            ['name'=>'Toucan Industries Ltd.','account_no'=>'ABL 100159748','address'=>'1380 Forest Drive, Belmopan','email'=>'soiladominguez@gmail.com','contact'=>'Mrs. Soila Salazar','phone'=>'615-5553','mobile'=>'822-2200','tax_id'=>'122866'],
            ['name'=>'Tracpac','account_no'=>'BBL 232450010120025','address'=>'Spanish Lookout, Cayo','email'=>'sales@tracpacbz.com','contact'=>null,'phone'=>'823-0321','mobile'=>null,'tax_id'=>'210384'],
            ['name'=>'Traksolution Belize Ltd','account_no'=>'BBL 232452010120026','address'=>'3 Miles George Price Highway','email'=>'accts.traksolution@gmail.com','contact'=>'Robert Peyrefitte','phone'=>'610-2512','mobile'=>null,'tax_id'=>'203824'],
            ['name'=>'Treasured Memories','account_no'=>'ABL 2110054864','address'=>'Santa Familia Cayo District','email'=>'tmphotoboothbz@gmail.com','contact'=>'Javier/Orissa Molina','phone'=>'678-8867','mobile'=>null,'tax_id'=>'362082'],
            ['name'=>'Trend Security Solution Ltd.','account_no'=>'BBL 163572010120001','address'=>'22 Dean Street, Belize City','email'=>'ssecofbelize@gmail.com','contact'=>'Stanley Leslie','phone'=>'675-6827','mobile'=>null,'tax_id'=>'222247'],
            ['name'=>'Triple J Furniture','account_no'=>'HBL 22141009','address'=>'George Price Avenue, Santa Elena','email'=>'dcdiana.dubon56@gmail.com','contact'=>'Diana Carolina Dubon','phone'=>'804-4800','mobile'=>'615-4583','tax_id'=>'19474313'],
            ['name'=>'Trofeos Melchor','account_no'=>'ABL 2120028400','address'=>'Barria Fallabon Melchor de Mencos, Peten Guatemala','email'=>'chingarma2019@gmail.com','contact'=>'Oscar Rene Garma','phone'=>'502-487-69224','mobile'=>null,'tax_id'=>'N/A'],

            // ---------- Page 8 ----------
            ['name'=>'Tropic Air Limited','account_no'=>'ABL 100031137','address'=>'Manta Ray San Pedro, Belize','email'=>'receivables@tropicair.com','contact'=>'Lucy Marin','phone'=>'226-3276','mobile'=>null,'tax_id'=>'005419'],
            ['name'=>'Tropic Rice','account_no'=>'HBL 6141117','address'=>'Spanish Lookout, Cayo','email'=>'ray@tropicrice.bz','contact'=>'Ray Dueck','phone'=>'675-1185','mobile'=>null,'tax_id'=>'221667'],
            ['name'=>'Tropical Palace Hotel','account_no'=>'BBL 249222010120000','address'=>'Coconut Drive, San Pedro Town, Ambergris Caye','email'=>'tropicalpalacehotelbelize@gmail.com','contact'=>'Mohamad Harmouch','phone'=>'627-5454','mobile'=>null,'tax_id'=>null],
            ['name'=>'Tropical Vision Limited','account_no'=>'ABL 2110000640','address'=>'Tropical Vision Limited, 73 Ablert Street, Belize City','email'=>'channel7ads@gmail.com','contact'=>'Melissa Ramsey','phone'=>'227-3988','mobile'=>null,'tax_id'=>'000017'],
            ['name'=>'Tropicana','account_no'=>'BBL 234018010120025','address'=>'33 Albert Street, Belize City, Belize District','email'=>'tropicana33albert@gmail.com','contact'=>'Bhindu Hotchandani','phone'=>'227-2882','mobile'=>'632-2656','tax_id'=>'101557'],
            ['name'=>'Turneffe Atoll Sustainability Assoc','account_no'=>'ABL 2110000825','address'=>'Cor. Freetown Rd and Cleghorns Street','email'=>'office@tasabelize.com','contact'=>'Valdemar Amdrade','phone'=>'223-1927','mobile'=>null,'tax_id'=>'198375'],
            ['name'=>'Tyre Center of Belize','account_no'=>'BBL 186930010120000','address'=>'Santa Elena, Cayo Belize','email'=>'tyrecenterofbelize1@gmail.com','contact'=>'Noheli Habet/Ingrid Gonzalez','phone'=>'824-3005','mobile'=>null,'tax_id'=>'216543'],
            ['name'=>'Uarau Drumming School','account_no'=>'BBL 242616010120000','address'=>'19 Honey Campy Street, Belmopan','email'=>'uaraudrumming@gmail.com','contact'=>'Joshua Arana','phone'=>'671-2672','mobile'=>null,'tax_id'=>null],
            ['name'=>'Unicomer Belize Ltd.','account_no'=>'ABL 100067693','address'=>'2.5 Miles Philip Goldson Highway','email'=>'tyron-anthony@unicomer.com','contact'=>'Tyron Anthony','phone'=>'615-8560','mobile'=>null,'tax_id'=>'5110'],
            ['name'=>'Unique Co. Ltd.','account_no'=>'BBL 118149010120001','address'=>'49.5 Miles Western Highway Belmopan, Cayo District Belize','email'=>'belize_unique@hotmail.com','contact'=>'Susan Wu','phone'=>'822-0462','mobile'=>'628-8199','tax_id'=>'000281'],
            ['name'=>'Uniquely Handmade Craft','account_no'=>'BBL 232959010120025','address'=>'8088 Gladden St.','email'=>'champayne_17@yahoo.com','contact'=>'Champayne Davis','phone'=>'614-3103','mobile'=>null,'tax_id'=>'158570'],
            ['name'=>'Val-U Hardware','account_no'=>'BBL 177639010120000','address'=>'Alejandro Vernon Street, Punta Gorda,','email'=>'valuhardware63@gmail.com','contact'=>'Ephriam Supaul','phone'=>'614-4999','mobile'=>null,'tax_id'=>'033412'],
            ['name'=>'Valdez Electrical Workshop and Auto Parts','account_no'=>'ABL 100144416','address'=>'31 Teodoso Ochoa Street','email'=>'valdezelectricworkshop@gmail.com','contact'=>'Keila Valdez','phone'=>'613-8615','mobile'=>null,'tax_id'=>'003804'],
            ['name'=>'Vales Procraft Printing','account_no'=>'BBL 159258010120001','address'=>'42 George Price Highway Cayo District','email'=>'torresprint@yahoo.com','contact'=>'Marivel Torres','phone'=>'614-3171','mobile'=>null,'tax_id'=>'196751'],
            ['name'=>'Veggie Garden','account_no'=>'BBL 159297010120001','address'=>'1523 Hummingbird Highway Belmopan City','email'=>'egglu173@hotmail.com','contact'=>'Heng Ju Lin','phone'=>'601-8588','mobile'=>null,'tax_id'=>'086743'],
            ['name'=>'Villa San Juan Mirror Enterprises Limited','account_no'=>'HBL 6131002','address'=>'#17 Tangelo Street Cohune Walk, Belmopan Belize','email'=>'info@villasanjuanbelmopan.com','contact'=>'Jorge Omar Espejo Sr.','phone'=>'610-0618','mobile'=>null,'tax_id'=>'085437'],
            ['name'=>'Vin Fa Shopping Center','account_no'=>'BBL 234052010120025','address'=>'93 Cemetery Road, Belize City','email'=>'wendyau10@gmail.com','contact'=>'Wendy Ou','phone'=>'614-9891','mobile'=>null,'tax_id'=>'174245'],
            ['name'=>'Vira Group Company Limited','account_no'=>'ABL 100145861','address'=>'#53 Queen Street, Belize City','email'=>'sgutierrez@cwbze.com','contact'=>'Sonia Gutierrez / Zully Ventura','phone'=>'223-5125','mobile'=>'610-9242','tax_id'=>'1301133'],
            ['name'=>'Vital Topco, LP dba Vital Source Technologies LLC','account_no'=>'Bank of America','address'=>'227 Fayetteville Street, Ste 400','email'=>'vst.credit@vitalsource.com','contact'=>'Nancy Stout','phone'=>'615-796-9763','mobile'=>null,'tax_id'=>'N/A'],
            ['name'=>"Viv's Healing Hands Services",'account_no'=>'ABL 2120004853','address'=>'#469 Lester Richard Ext. Independence Village','email'=>'viviguamu@gmail.com','contact'=>'Lic. Vivet Palacio','phone'=>'624-7114','mobile'=>null,'tax_id'=>'046319'],
            ['name'=>'Wadani Glass & Aluminum Ltd','account_no'=>'BBL 257584010120000','address'=>'Water Supply Area, Punta Gorda Town','email'=>'paulino34@gmail.com','contact'=>'Edwin Paulino','phone'=>'671-0895','mobile'=>null,'tax_id'=>'343045'],
            ['name'=>'Walter Alcides Peña','account_no'=>'ABL 211823044','address'=>'Shu Lab St. Maya Mopan','email'=>'walterssm11@gmail.com','contact'=>'Walter Peña','phone'=>'677-4457','mobile'=>null,'tax_id'=>'231195'],
            ['name'=>'Way Printing Co. Ltd.','account_no'=>'ABL 100128336','address'=>'3317 Central American Boulevard Belize City, Belize','email'=>'wayprintingbill@gmail.com','contact'=>'Tania Young','phone'=>'227-3799','mobile'=>null,'tax_id'=>'229264'],
            ['name'=>'Western Gas Company Ltd.','account_no'=>'BBL 130395010120001','address'=>'#1 Edwardo Juan Street, Santa Elena, Cayo','email'=>'westerngasco@yahoo.com','contact'=>'Priscilla Pinelo','phone'=>'824-3209','mobile'=>'615-8229','tax_id'=>'005085'],
            ['name'=>'Western Homes Supplier','account_no'=>'BBL 232383010120027','address'=>'Esperanza Village, Cayo','email'=>'marvinorellano@gmail.com','contact'=>'Marvin Orellano','phone'=>'824-0484','mobile'=>'671-9557','tax_id'=>'166730'],
            ['name'=>'Western Rebuilders','account_no'=>'BBL 133853010120001','address'=>'Cohune Ave. Spanish Lookout Belize','email'=>'westernrebuilders@gmail.com','contact'=>'Jacob Hein','phone'=>'823-0211','mobile'=>null,'tax_id'=>'015760'],
            ['name'=>'Westline Bus Company Limited','account_no'=>'ABL 100295770','address'=>'1 George Street Benque Viejo Town','email'=>'chucandchuc@yahoo.com','contact'=>'Sergio Chuc','phone'=>'6105356','mobile'=>null,'tax_id'=>'199037'],
            ['name'=>'Westrac Ltd.','account_no'=>'BBL 121054010120001','address'=>'Spanish Lookout, Cayo','email'=>'credit@westracbelize.com','contact'=>'Wendy Gregorio','phone'=>'823-0104','mobile'=>null,'tax_id'=>'015410'],
            ['name'=>'Wilford Felix (Mr.)','account_no'=>'ABL 211432476','address'=>'86 Barrack Road, Belize City','email'=>'wilford.a.felix@gmail.com','contact'=>'Wilford Felix','phone'=>'623-7475','mobile'=>null,'tax_id'=>'346858'],
            ['name'=>'Windell Thomas','account_no'=>'ABL 2120011957','address'=>'Camalote Village','email'=>'windellthomas88@gmail.com','contact'=>'Windel Thomas','phone'=>'611-9652','mobile'=>null,'tax_id'=>'374490'],
            ['name'=>'Wine Smith Ltd','account_no'=>'BBL 162708010120001','address'=>'73 Bullet Tree Road, San Ignacio Cayo','email'=>'winesmithltd@gmail.com','contact'=>'Rhyan Smith','phone'=>'615-6678','mobile'=>'624-6678','tax_id'=>'238673'],
            ['name'=>'Wood Stop Ltd','account_no'=>'BBL 149887010120001','address'=>'Forest Drive, Belmopan City','email'=>'accountant@themenagroup.biz','contact'=>'Andres Sanchez','phone'=>'822-2387','mobile'=>null,'tax_id'=>'181487'],
            ['name'=>'Yim Saan Restaurant & Hotel','account_no'=>'ABL 100225819','address'=>'Hummingbird Highway, Belmopan.','email'=>'yimsaan@yahoo.com','contact'=>'Jian Chen','phone'=>'614-1356','mobile'=>null,'tax_id'=>'016939'],
            ['name'=>'Yolanda Robinson','account_no'=>'HBL 7664','address'=>'52 Starky Hill, Belmopan','email'=>'yrobs5@yahoo.com','contact'=>'Yolanda Robinson','phone'=>'615-4472','mobile'=>null,'tax_id'=>'007664'],
            ['name'=>'Yute Expedition Ltd.','account_no'=>'BBL 140927010120001','address'=>'#7 5th Street, San Ignacio','email'=>'sfigueroa@yuteexp.com','contact'=>'Sharon Figueroa','phone'=>'824-2076','mobile'=>'610-0408','tax_id'=>'000069'],
            ['name'=>'Zoom Freight Ltd.','account_no'=>'BBL 278878010120000','address'=>'71 San Antonio Road','email'=>'Zoomfreightllc@outlook.com','contact'=>'Charles Carlos','phone'=>'2136713005','mobile'=>'2136143063','tax_id'=>'371942'],
            ['name'=>'Zun Rong Glass Shop','account_no'=>'ABL 100292764','address'=>'2093 Coney Drive, Belize City','email'=>'chenliying740@gmail.com','contact'=>'Living Chen','phone'=>'635-9057','mobile'=>null,'tax_id'=>'225917'],
        ];
    }
}
