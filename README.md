[**English version**][ext0]

# Moduł PayU dla PrestaShop 1.6 i 1.7
``Moduł jest wydawany na licencji GPL.``

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym wsparciem pod adresem: tech@payu.pl.**

Uwaga: plugin w [wersji 2.x](https://github.com/PayU/plugin_prestashop/tree/2.x) wspiera PrestaShop w wersji 1.4 i 1.5, ale nie jest dalej rozwijany.

## Spis treści

* [Cechy i kompatybilność](#cechy-i-kompatybilność)
* [Wymagania](#wymagania) 
* [Instalacja](#instalacja)
* [Aktualizacja](#aktualizacja)
* [Konfiguracja](#konfiguracja)
* [Więcej o cechach](#więcej-o-cechach)
    * [Wielowalutowość](#wielowalutowość)
    * [Wyświetlenie metod płatności](#wyświetlenie-metod-płatności)
    * [Ponowienie płatności](#ponowienie-płatności)
    * [Promowanie płatności ratalnych i odroczonych](#promowanie-płatności-ratalnych-i-odroczonych)

## Cechy i kompatybilność
Moduł płatności PayU dodaje do PrestaShop opcję płatności PayU i pozwala na następujące operacje:

Plugin w wersji 3.x wspiera PrestaShop w wersji 1.6 i 1.7

| Cecha | PrestaShop 1.6 | PrestaShop 1.7 |
|---------|:-----------:|:-----------:|
| Utworzenie płatności (wraz z rabatami) | :white_check_mark: | :white_check_mark: |
| Odebranie lub odrzucenie płatności (w przypadku wyłączonego autoodbioru) | :white_check_mark: | :white_check_mark: |
| Utworzenie zwrotu (pełnego lub częściowego) | :white_check_mark: | :white_check_mark: |
| Wyświetlenie metod płatności i wybranie metody na stronie podsumowania zamówienia | :white_check_mark: | :white_check_mark: |
| Ponowienie płatności przez klienta w przypadku anulowania | :white_check_mark: | :white_check_mark: |
| Wielowalutowość | :white_check_mark: | :white_check_mark: |
| Kolejność metod płatności | :white_check_mark: | :white_check_mark: |
| Promowanie [PayU Raty][ext10] i [PayU Płacę Później][ext9] | :white_check_mark: | :white_check_mark: |
| Prezentacja kalkulacji ratalnej przy produkcie i listingu | :white_check_mark: | :white_check_mark: |
| Prezentacja kalkulacji ratalnej na podsumowaniu | :x: | :white_check_mark: |
| Prezentacja kalkulacji ratalnej w koszyku | :x: | :white_check_mark: |

Więcej informacji o cechach można znaleźć w rozdziale [Więcej o cechach](#więcej-o-cechach) 

**Wszyskie opisy w tej instrukcji odnoszą się do PrestaShop 1.6, w wersji 1.7 należy używać opcji analogicznych**.

## Wymagania

**Ważne:** Moduł ta działa tylko z punktem płatności typu `REST API` (Checkout).
Jeżeli nie posiadasz jeszcze konta w systemie PayU [**zarejestruj się w systemie produkcyjnym**][ext4] lub [**zarejestruj się w systemie sandbox**][ext5]

Do prawidłowego funkcjonowania modułu wymagane są następujące rozszerzenia PHP: [cURL][ext1] i [hash][ext2].

## Instalacja

### Opcja 1 
**przeznaczona dla użytkowników bez dostępu poprzez FTP do instalacji PrestaShop**

1. Pobierz moduł z [repozytorium GitHub][ext3] jako plik zip
1. Rozpakuj pobrany plik
1. **Utwórz archiwum zip z katalogu `payu`**
1. Przejdź do strony administracyjnej swojego sklepu PrestaShop [http://adres-sklepu/adminxxx].
1. Przejdź do `Moduły` » `Moduły i usługi`
1. Naciśnij przycisk `Dodaj nowy moduł` i wybierz plik z archiwum modułu (utworzonej w punkcie 3)
1. Naciśnij przycisk `Prześlij moduł`

### Opcja 2 
**przeznaczona dla użytkowników z dostępem poprzez FTP do instalacji PrestaShop**

1. Pobierz moduł z [repozytorium GitHub][ext3] jako plik zip
1. Rozpakuj pobrany plik
1. Połącz się z serwerem ftp i skopiuj katalog `payu` z rozpakowanego pliku do katalogu `modules` swojego sklepu PrestaShop  

## Aktualizacja

1. Zaktualizuj piki moduł zgodnie z punkctem [Instalacja](#instalacja)
1. Przejdź do `Moduły` » `Moduły i usługi` - zostanie przeprowadzona automatyczna aktualizacja modułu jeżli jest wymagana 
1. Przejdź do `Parametry zaawansowane` » `Wydajność` i naciśnij przycisk `Wyczyść pamięć podręczną`  

## Konfiguracja

1. Przejdź do strony administracyjnej swojego sklepu PrestaShop [http://adres-sklepu/adminxxx].
1. Przejdź do `Moduły` » `Moduły i usługi`
1. Wyszukaj `PayU` i naciśnij `Konfiguruj`

### Sposób integracji

| Parameter | Opis | 
|---------|-----------|
| Wyświetlaj metody płatności na stronie podsumowania zamówienia w PrestaShop | **Tak** - metody płatności zostaną wyświetlone na stronie podsumowania zamówienia w PrestaShop<br>**Nie** - po złożeniu zamówienia a PrestaShop nastąpi automatyczne przekierwoanie do PayU |
| Kolejność metod płatności | Określa kolejność wyświetlanych metod płatności [więcej informacji](#kolejność-metod-płatności). |
| Tryb testowy (Sandbox) | **Tak** - transakcje będą procesowane przez system Sandbox PayU<br>**Nie** - transakcje będą procesowane przez system produkcyjny PayU |

### Parametry POS-ów

Dla każdej waluty dodanej w PrestaShop należy dodać parametry (osobno dla środowiska produkcyjnego i sandbox):

| Parameter | Opis | 
|---------|-----------|
| Id punktu płatności| Identyfikator POS-a z systemu PayU |
| Drugi klucz MD5 | Drugi klucz MD5 z systemu PayU |
| OAuth - client_id | client_id dla protokołu OAuth z systemu PayU |
| OAuth - client_secret | client_secret for OAuth z systemu PayU |

### Statusy płatności
Mapowanie statusów płatności w PayU na statusy w skepie PrestaShop

| Nazwa | Status w PayU | Domyślny status w Presta | 
|---------|-----------|-----------|
| Rozpoczęta | `NEW` i `PENDING` | Płatność PayU rozpoczęta |
| Oczekuje na odbiór | `WAITING_FOR_CONFIRMATION` i `REJECTED` | Płatność PayU oczekuje na odbiór |
| Zakończona | `COMPLETED` | Płatność zaakceptowana |
| Anulowana | `CANCELED` | Płatność PayU anulowana |

## Więcej o cechach

### Kolejność metod płatności
Ma zastosowanie tylko przy ustawionej opcji **Wyświetlaj metody płatności na stronie podsumowania zamówienia w PrestaShop** na `Tak`

W celu ustalenia kolejności wyświetlanych ikon matod płatności należy podać symbole metod płatności oddzielając je przecinkiem. [Lista metod płatności][ext6].

### Wielowalutowość
POS w systemie PayU ma jedną walutę. Jeżeli chcemy akceptować płatność w sklepie PrestaShop w wielu walutach niezbędne jest dodanie konfiguracji POSa dla każdej waluty z osobna.   

### Wyświetlenie metod płatności 
Przy ustawionej opcji **Wyświetlaj metody płatności na stronie podsumowania zamówienia w PrestaShop** na `Tak` po wybraniu płatności przez PayU wyświetli się strona z ikonami banków bezpośrednio w sklepie PrestaShop.
Ikony banków, które są wyświetlane pobierane są z konfiguracji POS-a w PayU w zależności od wybranej waluty.  

![payment_methods][img3]

Po wybraniu banku lub płatności kartą i naciśnięciu przycisku `Potwierdzam zamówienie i płacę` nastąpi bezpośrednie przekierowanie na stronę banku lub w przypadku płatności kartą na stronę formatki kartowej.  

### Ponowienie płatności
W przypadku nieudanej płatności w PayU możliwe jest ponowienie takiej płatności samodzielnie przez kupującego.

Żeby kupujący mógł ponowić płatność muszą być spełnione następujace warunki: 
* status ostatniej płatności z PayU musi mieć status CANCELED
* status zamówienia w PrestaShop musi być zgodny ze statusem wybranym w konfiguracji wtyczki `Statusy płatności` » `Anulowana`    

Kupującemu w `Szczegółach zamówinia` wyświetlany jest przycisk `Ponów płatność z PayU`.

![retry_payment][img4]

W panelu administracyjnym w szczegółach zamówienia wyświetlane są wszystkie utworzone płatności w PayU wraz ze statusami.

### Promowanie płatności ratalnych i odroczonych
Od wersji 3.0.9 plugin udostępnia opcję promowania [płatności ratalnych i odroczonych][ext7].
Funkcjonalność jest domyślnie włączona. Można ją dezaktywować poprzez przełącznik "Promuj płatności ratalne" w panelu
 admińskim. Wsparcie dla konkretnych funkcjonalności przedstawione zostało w tabeli [Cechy i 
 Kompatybilność](#cechy-i-kompatybilność).
 > Prezentacja kalkulacji zależna jest od dostępności bramek "ai" oraz "dp" na danym punkcie płatności i sprawdzana 
 jest automatycznie przez plugin. Jeśli na pukcie płatności nie zostały aktywowane Raty PayU kalkulacja nie zostanie 
 zaprezentowana pomimo włączonej opcji w pluginie.
 
<img src="readme_images/credit-1-7-admin.png" width="400">

#### Prezentacja kalkulacji w zależności od wersji PrestaShop
|Wersja PrestaShop|Kategoria|Prezentacja|
|---------|-----------|-----------|
|1.7|Listing produktów| <img src="readme_images/credit-1-7-listing.png" width="100"> |
|1.7|Karta produktu|<img src="readme_images/credit-1-7-product.png" width="100">|
|1.7|Koszyk| <img src="readme_images/credit-1-7-cart.png" width="100">|
|1.7|Wybór metody płatności PayU Raty|<img src="readme_images/credit-1-7-checkout-installments.png" width="100">|
|1.7|Wybór metody płatności PayU Płacę później| <img src="readme_images/credit-1-7-checkout-payu-later.png" width="100">| 
|1.6|Listing produktów|<img src="readme_images/credit-1-6-listing.png" width="100">|
|1.6|Karta produktu|<img src="readme_images/credit-1-6-product.png" width="100">|
|1.6|Wybór metody płatności|<img src="readme_images/credit-1-6-checkout.png" width="100">|


#### Prezentacja kalkulacji po kliknięciu w link "Rata już od:"
Plugin do kalkulacji kredytu używa najnowszej wersji elementu "miniratka" wchodzącego w skład pakietu [PayU Raty - 
dobre praktyki][ext12], który znajduje się w [oficjalnej dokumentacji technicznej][ext8].

Do prezentacji okna informacyjnego o [PayU Płacę Później][ext9] wykorzystywany jest [Widget Płacę Później][ext11].

Widget z kalkulacją ratalną wygląda następująco:
 
<img src="readme_images/credit-installment-widget.png" width="300">

<!--LINKS-->

<!--external links:-->
[ext0]: README.EN.md
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php
[ext3]: https://github.com/PayU/plugin_prestashop
[ext4]: https://secure.payu.com/boarding/?pk_campaign=Plugin-Github&pk_kwd=Prestashop#/form
[ext5]: https://secure.snd.payu.com/boarding/?pk_campaign=Plugin-Github&pk_kwd=Prestashop#/form
[ext6]: http://developers.payu.com/pl/overview.html#paymethods
[ext7]: https://developers.payu.com/pl/installments.html
[ext8]: https://developers.payu.com/pl/installments.html#installments_best_practices_mini
[ext9]: https://place-pozniej.payu.pl/
[ext10]: https://www.payu.pl/metody-platnosci-dla-biznesu/payu-raty
[ext11]: https://developers.payu.com/pl/installments.html#dp_best_practices_mini
[ext12]: https://developers.payu.com/pl/installments.html#best_practices_title

<!--images:-->
[img3]: readme_images/bramki_platnosci.png
[img4]: readme_images/ponow_platnosc.png
