[**English version**][ext0]

# Moduł PayU dla PrestaShop
``Moduł jest wydawana na licencji GPL.``

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym wsparciem pod adresem: tech@payu.pl.**

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

## Cechy i kompatybilność
Moduł płatności PayU dodaje do PrestaShop opcję płatności PayU i pozwala na następujące operacje:

| Cecha | Presta 1.4 | Presta 1.5 | Presta 1.6 | Presta 1.7 |
|---------|:-----------:|:-----------:|:-----------:|
| Utworzenie płatności (wraz z rabatami) | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Odebranie lub odrzucenie płatności (w przypadku wyłączonego autoodbioru) | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Utworzenie zwrotu (pełnego lub częściowego) | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Wyświetlenie metod płatności i wybranie metody na stronie podsumowania zamówienia | :x: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Ponowienie płatności przez klienta w przypadku anulowania | :x: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Wielowalutowość | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |

Więcej informacji o cechach można znaleźć w rozdziale [Więcej o cechach](#więcej-o-cechach) 

**Wszyskie opisy w tej instrukcji odnoszą się do PrestaShop 1.6, w wersji 1.5 i 1.4 należy używać opcji analogicznych**.

## Wymagania

**Ważne:** Moduł ta działa tylko z punktem płatności typu `REST API` (Checkout).

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


### Parametry POS-ów

Dla każdej waluty w dodanej w PrestaShop należy dodać parametry:

| Parameter | Opis | 
|---------|-----------|
| Id punktu płatności| Identyfikator POS-a z systemu PayU |
| Drugi klucz MD5 | Drugi klucz MD5 z systemu PayU |
| OAuth - client_id | client_id dla protokołu OAuth z systemu PayU |
| OAuth - client_secret | client_secret for OAuth z systemu PayU |

#### Przykład konfiguracji POS-a

PrestaShop:

![presta_pos_config][img1]

Konfiguracja POS-a w panelu PayU:

![pos_configuration_keys][img2]

### Statusy płatności
Mapowanie statusów płatności w PayU na statusy w skepie PrestaShop

| Nazwa | Status w PayU | Domyślny status w Presta | 
|---------|-----------|-----------|
| Rozpoczęta | `NEW` i `PENDING` | Płatność PayU rozpoczęta |
| Oczekuje na odbiór | `WAITING_FOR_CONFIRMATION` i `REJECTED` | Płatność PayU oczekuje na odbiór |
| Zakończona | `COMPLETED` | Płatność zaakceptowana |
| Anulowana | `CANCELED` | Płatność PayU anulowana |

## Więcej o cechach

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

<!--LINKS-->

<!--external links:-->
[ext0]: README.EN.md
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php
[ext3]: https://github.com/PayU/plugin_prestashop

<!--images:-->
[img1]: https://raw.github.com/PayU/plugin_prestashop/master/readme_images/presta_pos_config.png
[img2]: https://raw.github.com/PayU/plugin_prestashop/master/readme_images/pos_configuration_keys.png
[img3]: https://raw.github.com/PayU/plugin_prestashop/retryPayment/readme_images/bramki_platnosci.png
[img4]: https://raw.github.com/PayU/plugin_prestashop/retryPayment/readme_images/ponow_platnosc.png