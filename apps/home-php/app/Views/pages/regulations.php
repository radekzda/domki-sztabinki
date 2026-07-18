<?php

declare(strict_types=1);

/**
 * @var string|null $bookingRules
 */

$additionalRules = isset($bookingRules)
    && is_string($bookingRules)
        ? trim($bookingRules)
        : '';
?>

<style>
    .regulations-page {
        padding: 48px 0 72px;
        background: #f8fafc;
    }

    .regulations-page__content {
        max-width: 900px;
        margin: 0 auto;
        padding: 32px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        line-height: 1.75;
        color: #334155;
    }

    .regulations-page h1 {
        margin-top: 0;
        margin-bottom: 12px;
        color: #0f172a;
        font-size: 34px;
        line-height: 1.2;
    }

    .regulations-page h2 {
        margin-top: 36px;
        margin-bottom: 14px;
        color: #0f172a;
        font-size: 22px;
    }

    .regulations-page p {
        margin: 0 0 14px;
    }

    .regulations-page ol {
        margin: 0;
        padding-left: 24px;
    }

    .regulations-page li {
        margin-bottom: 10px;
    }

    .regulations-page a {
        color: #0369a1;
    }

    .regulations-page__intro {
        margin-bottom: 28px;
        color: #64748b;
    }

    .regulations-page__additional {
        margin: 28px 0;
        padding: 20px;
        border: 1px solid #f0d5a9;
        border-radius: 12px;
        background: #fffaf0;
    }

    .regulations-page__additional h2 {
        margin-top: 0;
    }

    .regulations-page__back {
        margin-top: 40px;
    }

    @media (max-width: 700px) {
        .regulations-page {
            padding: 24px 0 48px;
        }

        .regulations-page__content {
            padding: 22px;
            border-radius: 12px;
        }

        .regulations-page h1 {
            font-size: 28px;
        }
    }
</style>

<section class="regulations-page">
    <div class="container">
        <article class="regulations-page__content">
            <h1>
                Regulamin Domków Wczasowych Sztabinki
            </h1>

            <p class="regulations-page__intro">
                Regulamin określa zasady rezerwacji,
                pobytu oraz korzystania z domków
                i terenu obiektu Domki Sztabinki.
            </p>

            <?php if ($additionalRules !== ''): ?>
                <section class="regulations-page__additional">
                    <h2>
                        Najważniejsze zasady pobytu
                    </h2>

                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                $additionalRules,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </p>
                </section>
            <?php endif; ?>

            <h2>
                §1. Postanowienia ogólne
            </h2>

            <ol>
                <li>
                    Niniejszy regulamin określa zasady rezerwacji,
                    pobytu oraz korzystania z domków wczasowych
                    Domki Sztabinki i terenu należącego do obiektu.
                </li>

                <li>
                    Osobą odpowiedzialną za prowadzenie obiektu
                    jest Radosław Zdancewicz, zwany dalej
                    „Wynajmującym”.
                </li>

                <li>
                    Regulamin jest udostępniany Gościom przed
                    dokonaniem rezerwacji oraz jest dostępny
                    na stronie internetowej obiektu.
                </li>

                <li>
                    Potwierdzenie rezerwacji po zapoznaniu się
                    z regulaminem oznacza akceptację jego
                    postanowień.
                </li>

                <li>
                    Obiekt przeznaczony jest przede wszystkim
                    do spokojnego wypoczynku. Organizowanie
                    głośnych imprez i wydarzeń zakłócających
                    pobyt innych osób jest zabronione.
                </li>

                <li>
                    Wynajmujący może odmówić dalszego świadczenia
                    usług osobom, które w sposób rażący
                    lub uporczywy naruszają regulamin,
                    zasady bezpieczeństwa albo zakłócają pobyt
                    innych Gości. W przypadku bezpośredniego
                    zagrożenia dla osób lub mienia interwencja
                    może nastąpić niezwłocznie.
                </li>
            </ol>

            <h2>
                §2. Rezerwacja i płatności
            </h2>

            <ol>
                <li>
                    Rezerwacja bezpośrednia zostaje potwierdzona
                    po uzgodnieniu terminu i ceny pobytu oraz
                    wpłacie zadatku w wysokości 10% wartości
                    pobytu, chyba że strony uzgodnią inaczej.
                </li>

                <li>
                    Wpłacony zadatek zostaje zaliczony na poczet
                    całkowitej ceny pobytu.
                </li>

                <li>
                    W przypadku niewykonania umowy z przyczyn
                    leżących po stronie Gościa Wynajmujący może
                    zachować otrzymany zadatek zgodnie
                    z obowiązującymi przepisami.
                </li>

                <li>
                    W przypadku niewykonania umowy z przyczyn
                    leżących po stronie Wynajmującego zastosowanie
                    mają zasady dotyczące zadatku wynikające
                    z obowiązujących przepisów.
                </li>

                <li>
                    W przypadku rozwiązania umowy za zgodą obu
                    stron albo gdy niewykonanie umowy wynika
                    z okoliczności, za które żadna ze stron
                    nie ponosi odpowiedzialności, rozliczenie
                    zadatku następuje zgodnie z obowiązującymi
                    przepisami.
                </li>

                <li>
                    Pozostała część ceny pobytu powinna zostać
                    uregulowana najpóźniej w dniu przyjazdu,
                    chyba że wcześniej uzgodniono inny sposób
                    lub termin płatności.
                </li>

                <li>
                    W przypadku rezerwacji dokonanej
                    za pośrednictwem Booking.com, Airbnb
                    lub innej platformy rezerwacyjnej zastosowanie
                    mają również warunki rezerwacji, płatności
                    i anulowania określone dla danej rezerwacji
                    przez tę platformę.
                </li>

                <li>
                    Rezerwacja dotyczy liczby osób podanej podczas
                    jej dokonywania. Pobyt większej liczby osób
                    wymaga wcześniejszej zgody Wynajmującego
                    i może wiązać się z dodatkową opłatą.
                </li>

                <li>
                    W przypadku rezerwacji usługi zakwaterowania
                    na konkretnie oznaczony termin Gościowi
                    nie przysługuje ustawowe prawo do odstąpienia
                    od umowy w terminie 14 dni właściwe dla
                    niektórych innych umów zawieranych
                    na odległość.
                </li>
            </ol>

            <h2>
                §3. Zakwaterowanie i wykwaterowanie
            </h2>

            <ol>
                <li>
                    Doba pobytowa rozpoczyna się o godzinie
                    15:00 w dniu przyjazdu i kończy o godzinie
                    11:00 w dniu wyjazdu.
                </li>

                <li>
                    Wcześniejsze zakwaterowanie lub późniejsze
                    wykwaterowanie jest możliwe wyłącznie
                    po wcześniejszym uzgodnieniu z Wynajmującym.
                </li>

                <li>
                    Przedłużenie pobytu wymaga wcześniejszego
                    uzgodnienia i potwierdzenia dostępności domku.
                </li>

                <li>
                    Gość powinien po przyjeździe zapoznać się
                    ze stanem domku i jego wyposażenia oraz
                    zgłosić Wynajmującemu zauważone usterki
                    lub braki.
                </li>

                <li>
                    Brak zgłoszenia nie pozbawia Gościa prawa
                    do późniejszego zgłoszenia usterki, jeżeli
                    zostanie ona ujawniona w trakcie pobytu.
                </li>

                <li>
                    W dniu wyjazdu Gość zobowiązany jest pozostawić
                    domek w stanie wynikającym z prawidłowego
                    użytkowania oraz zwrócić klucze w sposób
                    uzgodniony z Wynajmującym.
                </li>
            </ol>

            <h2>
                §4. Zasady korzystania z obiektu
            </h2>

            <ol>
                <li>
                    Goście zobowiązani są do dbania o porządek
                    w domku i jego otoczeniu oraz korzystania
                    z wyposażenia zgodnie z jego przeznaczeniem.
                </li>

                <li>
                    Odpady powinny być segregowane i umieszczane
                    w przeznaczonych do tego pojemnikach.
                </li>

                <li>
                    Cisza nocna obowiązuje od godziny
                    22:00 do 7:00.
                </li>

                <li>
                    Rażące lub powtarzające się naruszanie ciszy
                    nocnej, szczególnie pomimo zwrócenia uwagi,
                    może skutkować zakończeniem pobytu zgodnie
                    z obowiązującymi przepisami i zasadami
                    odpowiedzialności stron.
                </li>

                <li>
                    Palenie tytoniu oraz używanie otwartego ognia
                    wewnątrz domków jest zabronione.
                </li>

                <li>
                    Rozpalanie grilla lub ogniska jest dozwolone
                    wyłącznie w miejscach do tego przeznaczonych
                    i z zachowaniem zasad bezpieczeństwa
                    przeciwpożarowego.
                </li>

                <li>
                    Zabrania się samodzielnego dokonywania napraw,
                    przeróbek instalacji oraz używania urządzeń
                    stwarzających zagrożenie pożarowe
                    lub elektryczne.
                </li>

                <li>
                    Zwierzęta mogą przebywać na terenie obiektu
                    wyłącznie po wcześniejszym uzyskaniu zgody
                    Wynajmującego.
                </li>

                <li>
                    Właściciel lub opiekun zwierzęcia ponosi
                    odpowiedzialność za szkody wyrządzone
                    przez zwierzę na zasadach określonych
                    w obowiązujących przepisach.
                </li>
            </ol>

            <h2>
                §5. Jezioro, pomost i sprzęt rekreacyjny
            </h2>

            <ol>
                <li>
                    Korzystanie z jeziora, pomostu, łodzi,
                    kajaka, rowerków wodnych i innych urządzeń
                    rekreacyjnych wymaga zachowania szczególnej
                    ostrożności oraz przestrzegania zasad
                    bezpieczeństwa.
                </li>

                <li>
                    Osoby korzystające ze sprzętu wodnego
                    zobowiązane są używać udostępnionego
                    wyposażenia zgodnie z jego przeznaczeniem.
                </li>

                <li>
                    Dzieci i osoby niepełnoletnie mogą przebywać
                    nad wodą i korzystać ze sprzętu wodnego
                    wyłącznie pod nadzorem osoby dorosłej.
                </li>

                <li>
                    Zabrania się korzystania ze sprzętu wodnego
                    osobom znajdującym się pod wpływem alkoholu
                    lub innych środków ograniczających zdolność
                    bezpiecznego zachowania.
                </li>

                <li>
                    Gość zobowiązany jest niezwłocznie zgłosić
                    Wynajmującemu zauważone uszkodzenie sprzętu
                    lub sytuację mogącą stanowić zagrożenie.
                </li>

                <li>
                    Każda osoba korzystająca z jeziora i sprzętu
                    rekreacyjnego zobowiązana jest stosować się
                    do obowiązujących przepisów i zasad
                    bezpieczeństwa.
                </li>
            </ol>

            <h2>
                §6. Odpowiedzialność za szkody
            </h2>

            <ol>
                <li>
                    Gość odpowiada na zasadach określonych
                    w obowiązujących przepisach za szkody
                    powstałe z jego winy lub z winy osób,
                    za które ponosi odpowiedzialność.
                </li>

                <li>
                    W przypadku stwierdzenia szkody Gość powinien
                    niezwłocznie poinformować o niej Wynajmującego.
                </li>

                <li>
                    Gość nie odpowiada za normalne zużycie
                    wyposażenia wynikające z jego prawidłowego
                    użytkowania.
                </li>

                <li>
                    Wynajmujący ponosi odpowiedzialność
                    za niewykonanie lub nienależyte wykonanie
                    swoich obowiązków na zasadach wynikających
                    z obowiązujących przepisów.
                </li>

                <li>
                    Odpowiedzialność za rzeczy wniesione przez
                    Gości na teren obiektu określają właściwe
                    przepisy prawa. Postanowienia regulaminu
                    nie wyłączają odpowiedzialności Wynajmującego
                    w przypadkach, w których jej wyłączenie
                    lub ograniczenie jest prawnie niedopuszczalne.
                </li>
            </ol>

            <h2>
                §7. Bezpieczeństwo
            </h2>

            <ol>
                <li>
                    Goście zobowiązani są korzystać z wyposażenia
                    i instalacji znajdujących się w domku zgodnie
                    z ich przeznaczeniem.
                </li>

                <li>
                    Zabrania się używania urządzeń, które ze względu
                    na swoją konstrukcję lub sposób użycia mogą
                    powodować zagrożenie pożarowe, elektryczne
                    lub inne zagrożenie dla osób i mienia.
                </li>

                <li>
                    Goście zobowiązani są zapoznać się z lokalizacją
                    dostępnego wyposażenia przeciwpożarowego
                    i apteczki.
                </li>

                <li>
                    Każdą awarię, usterkę lub sytuację mogącą
                    stanowić zagrożenie należy niezwłocznie
                    zgłosić Wynajmującemu.
                </li>

                <li>
                    W przypadku zagrożenia życia lub zdrowia
                    należy w pierwszej kolejności wezwać
                    odpowiednie służby ratunkowe.
                </li>
            </ol>

            <h2>
                §8. Reklamacje
            </h2>

            <ol>
                <li>
                    Uwagi dotyczące pobytu lub świadczonych usług
                    należy zgłaszać Wynajmującemu możliwie szybko,
                    aby umożliwić usunięcie problemu jeszcze
                    w trakcie pobytu.
                </li>

                <li>
                    Reklamację można również złożyć na adres
                    e-mail:
                    <a href="mailto:radekzdancewicz@gmail.com">
                        radekzdancewicz@gmail.com
                    </a>.
                </li>

                <li>
                    Reklamacja powinna zawierać dane umożliwiające
                    identyfikację rezerwacji oraz opis zgłaszanego
                    problemu.
                </li>

                <li>
                    Niniejszy regulamin nie ogranicza praw Gościa
                    wynikających z bezwzględnie obowiązujących
                    przepisów prawa.
                </li>
            </ol>

            <h2>
                §9. Postanowienia końcowe
            </h2>

            <ol>
                <li>
                    W sprawach nieuregulowanych niniejszym
                    regulaminem zastosowanie mają właściwe
                    przepisy prawa polskiego, w szczególności
                    Kodeksu cywilnego oraz przepisy dotyczące
                    ochrony konsumentów.
                </li>

                <li>
                    Regulamin jest dostępny na stronie
                    internetowej Domków Sztabinki.
                </li>

                <li>
                    Zmiana regulaminu nie narusza praw nabytych
                    przez Gości, którzy dokonali rezerwacji
                    na warunkach obowiązujących w chwili
                    jej zawarcia.
                </li>

                <li>
                    Regulamin obowiązuje od dnia jego
                    opublikowania.
                </li>
            </ol>

            <div class="regulations-page__back">
                <a href="/">
                    ← Powrót do strony głównej
                </a>
            </div>
        </article>
    </div>
</section>