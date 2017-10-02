<?php
    const MAX_COUNT_S = 100000;
    const MAX_SERVICES = 10;
    const MAX_VARIATIONS = 3;
    const MAX_QUESTIONS_TYPE = 10;
    const MAX_CATEGORIES = 20;
    const MAX_SUB_CATEGORIES = 5;
    const WAITING_TIME_LINE = "C"; //C service_id[.variation_id] question_type_id[.category_id.[sub-category_id]] P/N date time
    const QUERY = "D"; //D service_id[.variation_id] question_type_id[.category_id.[sub-category_id]] P/N date_from[-date_to]
    const FIRST_ANSWER = "P";
    const NEXT_ANSWER = "N";

    function calculate( $array )
    {
        $time = $result = "";
        if ( is_array( $array ) && !empty( $array ) )
        {
            $s = $query = [];
            foreach ($array as $row)
            {
                if ( $row['spaceCount'] == 4 )
                {
                    list( $type, $service, $question, $answer, $date ) = explode( " ", $row['line'] );
                }
                elseif ( $row['spaceCount'] == 5 )
                {
                    list( $type, $service, $question, $answer, $date, $time ) = explode( " ", $row['line'] );
                }
                else
                {
                    continue;
                }

                if ( $type != QUERY && $type != WAITING_TIME_LINE )
                {
                    continue;
                }

                if ( strpos( $service, "." ) !== FALSE )
                {
                    list( $serviceId, $serviceVar ) = explode( ".", $service );
                    if ( $serviceId > MAX_SERVICES )
                    {
                        continue;
                    }
                    elseif ( $serviceVar > MAX_VARIATIONS )
                    {
                        continue;
                    }
                }
                elseif ( strpos( $service, "." ) === FALSE )
                {
                    if ( $service > MAX_SERVICES )
                    {
                        continue;
                    }
                }

                if ( strpos( $question, "." ) !== FALSE )
                {
                    $questionInfo = explode( ".", $question );
                    if ( count($questionInfo) == 3 )
                    {
                        if ( $questionInfo[0] > MAX_QUESTIONS_TYPE )
                        {
                            continue;
                        }
                        elseif ( $questionInfo[1] > MAX_CATEGORIES )
                        {
                            continue;
                        }
                        elseif ( $questionInfo[2] > MAX_SUB_CATEGORIES )
                        {
                            continue;
                        }
                    }
                    elseif ( count($questionInfo) == 2 )
                    {
                        if ( $questionInfo[0] > MAX_QUESTIONS_TYPE )
                        {
                            continue;
                        }
                        elseif ( $questionInfo[1] > MAX_CATEGORIES )
                        {
                            continue;
                        }
                    }
                }
                elseif ( strpos( $question, "." ) === FALSE )
                {
                    if ( $question > MAX_QUESTIONS_TYPE )
                    {
                        continue;
                    }
                }

                if( $answer != FIRST_ANSWER && $answer != NEXT_ANSWER )
                {
                    continue;
                }

                if ( $type == QUERY )
                {
                    $query[] = [
                        "type" => $type,
                        "service" => $service,
                        "question" => $question,
                        "answer" => $answer,
                        "date" => $date,
                    ];
                }
                elseif( $type == WAITING_TIME_LINE )
                {
                    $s[] = [
                        "type" => $type,
                        "service" => $service,
                        "question" => $question,
                        "answer" => $answer,
                        "date" => $date,
                        "time" => $time
                    ];
                }
            }

            foreach ( $query as $queryRow )
            {
                $subTotal = $count = 0;
                foreach ( $s as $sRow )
                {
                    if ( strpos( $sRow['service'], "." ) !== FALSE )
                    {
                        $services = explode( ".", $sRow['service'] );
                        $serviceId = $services[0];
                    }
                    else
                    {
                        $serviceId = $sRow['service'];
                    }

                    if ( strpos( $queryRow['service'], "." ) !== FALSE )
                    {
                        $queryServices = explode( ".", $queryRow['service'] );
                        $queryServiceId = $queryServices[0];
                    }
                    else
                    {
                        $queryServiceId = $queryRow['service'];
                    }

                    if ( strpos( $sRow['question'], "." ) !== FALSE )
                    {
                        $question = explode( ".", $sRow['question'] );
                        $questionType = $question[0];
                    }
                    else
                    {
                        $questionType = $sRow['question'];
                    }

                    if ( strpos( $queryRow['question'], "." ) !== FALSE )
                    {
                        $queryQuestion = explode(".", $queryRow['question'] );
                        $queryQuestionType = $queryQuestion[0];
                    }
                    else
                    {
                        $queryQuestionType = $queryRow['question'];
                    }

                    if ( $serviceId == $queryServiceId || $queryServiceId == "*" )
                    {
                        if ( $questionType == $queryQuestionType || $queryQuestionType == "*" )
                        {
                            if ( strpos( $queryRow['date'], "-" ) !== FALSE )
                            {
                                list( $dateFrom, $dateTo ) = explode( "-", $queryRow['date'] );
                                $dateFrom = strtotime($dateFrom);
                                $dateTo = strtotime($dateTo);
                            }
                            else
                            {
                                $dateFrom = strtotime($queryRow['date']);
                                $dateTo = "";
                            }

                            if ( $dateFrom <= strtotime($sRow['date']) && ( $dateTo >= strtotime($sRow['date']) || $dateTo == "" ) )
                            {
                                $subTotal += $sRow['time'];
                                $count++;
                            }
                        }
                    }
                }
                $total = ( $subTotal == 0 ? $subTotal : $subTotal / $count );
                $result .= "<br>".( $total == 0 ? "-" : $total );
            }
        }

        return $result;
    }

    function render( $lineWrite, $message )
    {
        echo $message;
        echo $lineWrite;
    }

    $message = "";
    $lineWrite = '<form action="index.php" method="post">
        S: <input type="text" name="countS" value="">
        <input type="submit" name="continue" value="Continue">
        </form>';

    if ( isset( $_POST['continue'] ) && $_POST['continue'] == "Continue" )
    {
        if ( is_numeric( $_POST['countS'] ) && ( $_POST['countS'] <= MAX_COUNT_S ) )
        {
            $lineCount = $_POST['countS'];
            $lineWrite = '<form action="index.php" method="post">';
            for ( $i=0; $i<$lineCount; $i++ )
            {
                $lineWrite .= '<br><input type="text" name="line['.$i.']" value="">';
            }
            $lineWrite .= '<br><input type="submit" name="printOut" value="Result"></form>';
        }
        else
        {
            $message = "<p style='color: red;'>Please, entry service count between 1 to ".MAX_COUNT_S."</p>";
        }
    }
    elseif ( isset( $_POST['printOut'] ) && $_POST['printOut'] == "Result" )
    {
        if ( isset( $_POST['line'] ) && is_array( $_POST['line'] ) )
        {
            $lineCount = count($_POST['line']);
            $resultArray = [];
            $lineWrite = '<b>Input:</b><br>'.$lineCount;
            foreach ( $_POST['line'] as $row )
            {
                ltrim($row);
                rtrim($row);
                $lineWrite .= '<br>'.$row;
                $spaceCount = substr_count($row, ' ');
                $resultArray[] = [
                    "spaceCount" => $spaceCount,
                    "line" => $row
                ];
            }
            $lineWrite .= '<br><br><b>Output:</b>'.calculate( $resultArray );
            $lineWrite .= '<br>If you want to begin from start, you can click <a href="index.php">here</a>';
        }
        else
        {
            $message = "<p style='color: red;'>Lines is not found!</p>";
        }
    }

    render( $lineWrite, $message );