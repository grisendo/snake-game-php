<?php

const DIRECTION_TOP = 0;
const DIRECTION_RIGHT = 1;
const DIRECTION_BOTTOM = 2;
const DIRECTION_LEFT = 3;

const SPRITE_NONE = '&nbsp;';
const SPRITE_FOOD = '%';
const SPRITE_SNAKE = 'x';
const SPRITE_BORDER = '*';

const SCREEN_WIDTH = 80;
const SCREEN_HEIGHT = 40;

const COLLISION_NONE = 0;
const COLLISION_BORDER = 1;
const COLLISION_SNAKE = 2;
const COLLISION_FOOD = 3;

function loadData() {
    try {
        $data = @file_get_contents('state.json');
        if ($data) {
            $data = json_decode($data, TRUE);
        }
    } catch(Exception $e) {
        $data = null;
    }
    if (!$data) {
        $data = array();
        $data['snake'] = array(
            array(2, 2),
            array(2, 3),
            array(2, 4),
            array(2, 5),
            array(2, 6),
        );
        $data['food'] = array(rand(1, SCREEN_WIDTH - 2), rand(1, SCREEN_HEIGHT - 2));
        $data['direction'] = DIRECTION_BOTTOM;
        file_put_contents('state.json', json_encode($data));
    }
    return $data;
}

function saveData($data) {
    file_put_contents('state.json', json_encode($data));
}

function newFood(&$data) {
    $data['food'] = array(rand(1, SCREEN_WIDTH - 2), rand(1, SCREEN_HEIGHT - 2));
}

function updateScenario(&$canvas, $data) {
    for ($x = 0; $x < SCREEN_WIDTH; $x++) {
        for ($y = 0; $y < SCREEN_HEIGHT; $y++) {
            if ($x == 0 || $x == (SCREEN_WIDTH - 1) || $y == 0 || $y == (SCREEN_HEIGHT - 1)) {
                $canvas[$y][$x] = SPRITE_BORDER;
            } else {
                $canvas[$y][$x] = SPRITE_NONE;
            }
        }
    }
    $canvas[$data['food'][1]][$data['food'][0]] = SPRITE_FOOD;
    foreach ($data['snake'] as $point) {
        $canvas[$point[1]][$point[0]] = SPRITE_SNAKE;
    }
}

function updateSnake(&$data) {
    $newHead = $data['snake'][count($data['snake']) - 1];
    if ($data['direction'] == DIRECTION_BOTTOM) {
        $newHead[1] = $newHead[1] + 1;
    } elseif ($data['direction'] == DIRECTION_RIGHT) {
        $newHead[0] = $newHead[0] + 1;
    } elseif ($data['direction'] == DIRECTION_TOP) {
        $newHead[1] = $newHead[1] - 1;
    } elseif ($data['direction'] == DIRECTION_LEFT) {
        $newHead[0] = $newHead[0] - 1;
    }
    $oldSnake = array_values($data['snake']);
    $data['snake'][] = $newHead;
    if ($newHead[0] == $data['food'][0] && $newHead[1] == $data['food'][1]) {
        return COLLISION_FOOD;
    } else {
        array_shift($data['snake']);
    }
    if ($newHead[0] < 0 || $newHead[0] > (SCREEN_WIDTH - 1) || $newHead[1] < 0 || $newHead[1] > (SCREEN_HEIGHT - 1)) {
        return COLLISION_BORDER;
    }
    foreach ($oldSnake as $point) {
        if ($newHead[0] == $point[0] && $newHead[1] == $point[1]) {
            return COLLISION_SNAKE;
        }
    }
    return COLLISION_NONE;
}

function flush_buffers() {
    echo str_pad('', 4096);
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}

$canvas = array();
$threadTimestamp = time();
$data = loadData();
$data['timestamp'] = $threadTimestamp;
if (isset($_GET['direction'])) {
    $data['direction'] = $_GET['direction'];
}
saveData($data);

echo '<body style="font-family: monospace">';

if ($data['direction'] != DIRECTION_TOP) {
    echo '<a href="?direction=' . DIRECTION_TOP . '" accesskey="i"></a>';
}
if ($data['direction'] != DIRECTION_RIGHT) {
    echo '<a href="?direction=' . DIRECTION_RIGHT . '" accesskey="l"></a>';
}
if ($data['direction'] != DIRECTION_BOTTOM) {
    echo '<a href="?direction=' . DIRECTION_BOTTOM . '" accesskey="k"></a>';
}
if ($data['direction'] != DIRECTION_LEFT) {
    echo '<a href="?direction=' . DIRECTION_LEFT . '" accesskey="j"></a>';
}

updateScenario($canvas, $data);
ob_start();

while (true) {
    $data = loadData();
    if ($data['timestamp'] != $threadTimestamp) {
        break;
    }
    echo '<div style="position: absolute; width: 100%; text-align: center; background: #FFF;">';
    foreach ($canvas as $row) {
        foreach ($row as $cell) {
            echo $cell;
        }
        echo '<br/>';
    }
    echo '</div>';
    flush_buffers();
    $collision = updateSnake($data);
    if ($collision == COLLISION_BORDER || $collision == COLLISION_SNAKE) {
        echo '<div style="position: absolute; width: 100%; height: 100vh; text-align: center; background: #FFF;">';
        echo '<a href="?direction=' . DIRECTION_BOTTOM . '">GAME OVER</a>';
        echo '</div>';
        $data = null;
        saveData($data);
        break;
    }
    if ($collision == COLLISION_FOOD) {
        newFood($data);
    }
    updateScenario($canvas, $data);
    saveData($data);
    usleep(100000);
}
