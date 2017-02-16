<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>JavaScript Sudoku Solver</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="generator" content="amaya 8.5, see http://www.w3.org/Amaya/">
  <style type="text/css">
     body          { background: white; color: black }
     .center       { text-align: center }
     td.changed    { color: red }
  </style>
  <script type="text/javascript">
// Sudoku Solver in JavaScript.
// Copyright (C) 2005 John D. Ramsdell
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details at
// http://www.gnu.org/copyleft/gpl.html.

// Each cell has a value and set.  When the cell has been determined,
// value will be non-zero, otherwise it is zero.  The set has a bit
// set for each value of the cell that has yet to be eliminated as a
// possible value for this cell.  When a cell contains the empty set,
// the board is inconsistent.

// The basic dimension of the board
var dim = 3;
var dim2 = dim * dim;
// The set of all possibilities
var all = (1 << dim2) - 1;

// The cell constructor takes strings or numbers as arguments.
function Cell(val) {
  var cell = new Object;
  val = Number(val);
  if (0 < val && val <= 9) {
    cell.val = val;
    cell.set = 1 << (val - 1);
  }
  else {
    cell.val = 0;
    cell.set = all;
  }
  return cell;
}

// A board is a 3*3*3*3 array of cells.  In a board[i][j][k][l]
// reference, i is the major row, j is in minor row, k is the major
// column, and l is the minor column.

function mkarray(n) {
  var i;
  if (n <= 0)
    return null;
  n--;
  var a = new Array(dim);
  for (i = 0; i < dim; i++)
    a[i] = mkarray(n);
  return a;
}

// Construct a board from the form.
function Board() {
  var i, j, k, l;
  var board = mkarray(4);
  var e = 0;
  with (top.document.forms[0])
    for (i = 0; i < dim; i++)
      for (k = 0; k < dim; k++)
        for (j = 0; j < dim; j++)
          for (l = 0; l < dim; l++)
            board[i][j][k][l] = Cell(elements[e++].value);
  return board;
}

function duplicate(target, source) {
  var i, j, k, l;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (k = 0; k < dim; k++)
        for (l = 0; l < dim; l++) {
          target[i][j][k][l].val = source[i][j][k][l].val;
          target[i][j][k][l].set = source[i][j][k][l].set;
        }
}

function unknowns() {
  var i, j, k, l, m;
  var n = 0;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (k = 0; k < dim; k++)
        for (l = 0; l < dim; l++)
          n += card(board[i][j][k][l].set);
  return n;
}

// The number of elements in the set.
function card(set) {
  var m;
  var n = 0;
  for (m = 0; m < dim2; m++)
    if ((1 << m) & set)
      n++;
  return n;
}

// The current grid
var board;

// The previous grid
var previous;

function show_cell(i, j, k, l) {
  var cell = board[i][j][k][l];
  var set = cell.set & all;
  if (set == 0)
    return '?';
  if (cell.val > 0)
    return String(cell.val);
  if (set == all)
    return '.';
  // List possible values in cell.
  var i, m;
  var n = 0;
  var str = '';
  for (m = 0; m < dim2; m++)
    if ((1 << m) & set) {
      i = m + 1;
      str += String(i);
      n++;
    }
  if (n == 1) {
    // The value of the cell has been determined.
    str += "!";
    cell.val = i;
  }
  return str;
}

// Show current board position using HTML tables.
function show() {
  var bad = false;
  var tbody = document.createElement("tbody");
  for (i = 0; i < dim; i++) {
    var tri = document.createElement("tr");
    for (k = 0; k < dim; k++) {
      var ibody = document.createElement("tbody");
      for (j = 0; j < dim; j++) {
        var trj = document.createElement("tr");
        for (l = 0; l < dim; l++) {
          var text = show_cell(i, j, k, l);
          if (text == '?')
            bad = true;
          var node = document.createTextNode(text);
          var tdl = document.createElement("td");
          if (board[i][j][k][l].set != previous[i][j][k][l].set)
            tdl.setAttribute("class", "changed");
          tdl.setAttribute("align", "center");
          tdl.appendChild(node);
          trj.appendChild(tdl);
        }
        ibody.appendChild(trj);
      }
      var itbl = document.createElement("table");
      itbl.setAttribute("border", 1);
      itbl.setAttribute("width", "100%");
      itbl.appendChild(ibody);
      var tdk = document.createElement("td");
      tdk.setAttribute("align", "center");
      tdk.appendChild(itbl);
      tri.appendChild(tdk);
    }
    tbody.appendChild(tri);
  }
  var tbl = document.createElement("table");
  tbl.setAttribute("border", 1);
  tbl.setAttribute("align", "center");
  tbl.appendChild(tbody);
  document.body.appendChild(tbl);
  window.scrollBy(0, 1000);
  duplicate(previous, board);
  return bad;
}

// Show some text.
function put(text) {
  var node = document.createTextNode(text);
  var par = document.createElement("p");
  par.appendChild(node);
  document.body.appendChild(par);
  window.scrollBy(0, 100);
}

// The following functions implement the various solver strategies.
// Each strategy is described by text printed with its use in the
// solve function.  You can add your own strategies, or delete the
// ones here, and discover your own complete set of them.

function uniq(i, j, k, l) {
  var ii, jj, kk, ll;
  var set = ~board[i][j][k][l].set;

  /* handle row at i, j */

  for (kk = 0; kk < dim; kk++)
    for (ll = 0; ll < dim; ll++)
      if (k != kk || l != ll)
        board[i][j][kk][ll].set &= set;

  /* handle column at k, l */

  for (ii = 0; ii < dim; ii++)
    for (jj = 0; jj < dim; jj++)
      if (i != ii || j != jj)
        board[ii][jj][k][l].set &= set;

  /* handle square at i, k */

  for (jj = 0; jj < dim; jj++)
    for (ll = 0; ll < dim; ll++)
      if (j != jj || l != ll)
        board[i][jj][k][ll].set &= set;
}

function uniqall() {
  var i, j, k, l;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (k = 0; k < dim; k++)
        for (l = 0; l < dim; l++)
          if (board[i][j][k][l].val > 0)
            uniq(i, j, k, l);
}

function uniqsqr() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2; m++) {
        var p = 1 << m;
        var n = 0;
        var jj, ll;
        for (j = 0; j < dim; j++)
          for (l = 0; l < dim; l++)
            if (p & board[i][j][k][l].set) {
              n++;
              jj = j;
              ll = l;
            }
        if (n == 1)
          board[i][jj][k][ll].set = p;
      }
}

function uniqrow() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (m = 0; m < dim2; m++) {
        var p = 1 << m;
        var n = 0;
        var kk, ll;
        for (k = 0; k < dim; k++)
          for (l = 0; l < dim; l++)
            if (p & board[i][j][k][l].set) {
              n++;
              kk = k;
              ll = l;
            }
        if (n == 1)
          board[i][j][kk][ll].set = p;
      }
}

function uniqcol() {
  var i, j, k, l, m;
  for (k = 0; k < dim; k++)
    for (l = 0; l < dim; l++)
      for (m = 0; m < dim2; m++) {
        var p = 1 << m;
        var n = 0;
        var ii, jj;
        for (i = 0; i < dim; i++)
          for (j = 0; j < dim; j++)
            if (p & board[i][j][k][l].set) {
              n++;
              ii = i;
              jj = j;
            }
        if (n == 1)
          board[ii][jj][k][l].set = p;
      }
}

function uniqrowline() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2; m++) {
        var p = 1 << m;
        var n = 0;
        var jj;
        for (j = 0; j < dim; j++) {
          var q = 0;
          for (l = 0; l < dim; l++)
            if (p & board[i][j][k][l].set) {
              jj = j;
              q++;
            }
          if (q)
            n++;
        }
        if (n == 1) {
          var kk;
          for (kk = 0; kk < dim; kk++)
            if (k != kk)
              for (l = 0; l < dim; l++)
                board[i][jj][kk][l].set &= ~p;
        }
      }
}

function uniqcolline() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2; m++) {
        var p = 1 << m;
        var n = 0;
        var ll;
        for (l = 0; l < dim; l++) {
          var q = 0;
          for (j = 0; j < dim; j++)
            if (p & board[i][j][k][l].set) {
              ll = l;
              q++;
            }
          if (q)
            n++;
        }
        if (n == 1) {
          var ii;
          for (ii = 0; ii < dim; ii++)
            if (i != ii)
              for (j = 0; j < dim; j++)
                board[ii][j][k][ll].set &= ~p;
        }
      }
}

function uniqrowsqr() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (k = 0; k < dim; k++)
        for (m = 0; m < dim2; m++) {
          var p = 1 << m;
          var n = 0;
          var jj, kk;
          for (kk = 0; kk < dim; kk++)
            if (kk != k)
              for (l = 0; l < dim; l++)
                if (p & board[i][j][kk][l].set)
                  n++;
          if (n == 0)
            // here when m not in that part of the row
            for (jj = 0; jj < dim; jj++)
              if (jj != j)
                for (l = 0; l < dim; l++)
                  board[i][jj][k][l].set &= ~p;
        }
}

function uniqcolsqr() {
  var i, j, k, l, m;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (l = 0; l < dim; l++)
        for (m = 0; m < dim2; m++) {
          var p = 1 << m;
          var n = 0;
          var ii, ll;
          for (ii = 0; ii < dim; ii++)
            if (ii != i)
              for (j = 0; j < dim; j++)
                if (p & board[ii][j][k][l].set)
                  n++;
          if (n == 0)
            // here when m not in that part of the col
            for (ll = 0; ll < dim; ll++)
              if (ll != l)
                for (j = 0; j < dim; j++)
                  board[i][j][k][ll].set &= ~p;
        }
}

function pairsqr() {
  var i, j, l, k, m, mm;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2 - 1; m++)
        for (mm = m + 1; mm < dim2; mm++) {
          var p = (1 << m) | (1 << mm);
          var n = 0;
          for (j = 0; j < dim; j++)
            for (l = 0; l < dim; l++)
              if (p & board[i][j][k][l].set)
                n++;
          if (n == 2) {
            for (j = 0; j < dim; j++)
              for (l = 0; l < dim; l++) {
                var cell = board[i][j][k][l];
                if (p & cell.set)
                  cell.set &= p;
              }
          }
        }
}

function pairrow() {
  var i, j, k, l, m, mm;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (m = 0; m < dim2 - 1; m++)
        for (mm = m + 1; mm < dim2; mm++) {
          var p = (1 << m) | (1 << mm);
          var n = 0;
          for (k = 0; k < dim; k++)
            for (l = 0; l < dim; l++)
              if (p & board[i][j][k][l].set)
                n++;
          if (n == 2) {
            for (k = 0; k < dim; k++)
              for (l = 0; l < dim; l++) {
                var cell = board[i][j][k][l];
                if (p & cell.set)
                  cell.set &= p;
              }
          }
        }
}

function paircol() {
  var i, j, k, l, m, mm;
  for (k = 0; k < dim; k++)
    for (l = 0; l < dim; l++)
      for (m = 0; m < dim2 - 1; m++)
        for (mm = m + 1; mm < dim2; mm++) {
          var p = (1 << m) | (1 << mm);
          var n = 0;
          for (i = 0; i < dim; i++)
            for (j = 0; j < dim; j++)
              if (p & board[i][j][k][l].set)
                n++;
          if (n == 2) {
            for (i = 0; i < dim; i++)
              for (j = 0; j < dim; j++) {
                var cell = board[i][j][k][l];
                if (p & cell.set)
                  cell.set &= p;
              }
          }
        }
}

function triplesqr() {
  var i, j, l, k, m, mm, mmm;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2 - 2; m++)
        for (mm = m + 1; mm < dim2 - 1; mm++)
          for (mmm = mm + 1; mmm < dim2; mmm++) {
            var p = (1 << m) | (1 << mm) | (1 << mmm);
            var n = 0;
            for (j = 0; j < dim; j++)
              for (l = 0; l < dim; l++)
                if (p & board[i][j][k][l].set)
                  n++;
            if (n == 3) {
              for (j = 0; j < dim; j++)
                for (l = 0; l < dim; l++) {
                  var cell = board[i][j][k][l];
                  if (p & cell.set)
                    cell.set &= p;
                }
            }
          }
}

function triplerow() {
  var i, j, k, l, m, mm, mmm;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (m = 0; m < dim2 - 2; m++)
        for (mm = m + 1; mm < dim2 - 1; mm++)
          for (mmm = mm + 1; mmm < dim2; mmm++) {
            var p = (1 << m) | (1 << mm) | (1 << mmm);
            var n = 0;
            for (k = 0; k < dim; k++)
              for (l = 0; l < dim; l++)
                if (p & board[i][j][k][l].set)
                  n++;
            if (n == 3) {
              for (k = 0; k < dim; k++)
                for (l = 0; l < dim; l++) {
                  var cell = board[i][j][k][l];
                  if (p & cell.set)
                    cell.set &= p;
                }
            }
          }
}

function triplecol() {
  var i, j, k, l, m, mm, mmm;
  for (k = 0; k < dim; k++)
    for (l = 0; l < dim; l++)
      for (m = 0; m < dim2 - 2; m++)
        for (mm = m + 1; mm < dim2 - 1; mm++)
          for (mmm = mm + 1; mmm < dim2; mmm++) {
            var p = (1 << m) | (1 << mm) | (1 << mmm);
            var n = 0;
            for (i = 0; i < dim; i++)
              for (j = 0; j < dim; j++)
                if (p & board[i][j][k][l].set)
                  n++;
            if (n == 3) {
              for (i = 0; i < dim; i++)
                for (j = 0; j < dim; j++) {
                  var cell = board[i][j][k][l];
                  if (p & cell.set)
                    cell.set &= p;
                }
            }
          }
}

function quadsqr() {
  var i, j, l, k, m, mm, mmm, mmmm;
  for (i = 0; i < dim; i++)
    for (k = 0; k < dim; k++)
      for (m = 0; m < dim2 - 3; m++)
        for (mm = m + 1; mm < dim2 - 2; mm++)
          for (mmm = mm + 1; mmm < dim2 - 1; mmm++)
            for (mmmm = mmm + 1; mmmm < dim2; mmmm++) {
              var p = (1 << m) | (1 << mm) | (1 << mmm) | (1 << mmmm);
              var n = 0;
              for (j = 0; j < dim; j++)
                for (l = 0; l < dim; l++)
                  if (p & board[i][j][k][l].set)
                    n++;
              if (n == 4) {
                for (j = 0; j < dim; j++)
                  for (l = 0; l < dim; l++) {
                    var cell = board[i][j][k][l];
                    if (p & cell.set)
                      cell.set &= p;
                  }
              }
            }
}

function quadrow() {
  var i, j, k, l, m, mm, mmm;
  for (i = 0; i < dim; i++)
    for (j = 0; j < dim; j++)
      for (m = 0; m < dim2 - 3; m++)
        for (mm = m + 1; mm < dim2 - 2; mm++)
          for (mmm = mm + 1; mmm < dim2 - 1; mmm++)
            for (mmmm = mmm + 1; mmmm < dim2; mmmm++) {
              var p = (1 << m) | (1 << mm) | (1 << mmm) | (1 << mmmm);
              var n = 0;
              for (k = 0; k < dim; k++)
                for (l = 0; l < dim; l++)
                  if (p & board[i][j][k][l].set)
                    n++;
              if (n == 4) {
                for (k = 0; k < dim; k++)
                  for (l = 0; l < dim; l++) {
                    var cell = board[i][j][k][l];
                    if (p & cell.set)
                      cell.set &= p;
                  }
              }
            }
}

function quadcol() {
  var i, j, k, l, m, mm, mmm;
  for (k = 0; k < dim; k++)
    for (l = 0; l < dim; l++)
      for (m = 0; m < dim2 - 3; m++)
        for (mm = m + 1; mm < dim2 - 2; mm++)
          for (mmm = mm + 1; mmm < dim2 - 1; mmm++)
            for (mmmm = mmm + 1; mmmm < dim2; mmmm++) {
              var p = (1 << m) | (1 << mm) | (1 << mmm) | (1 << mmmm);
              var n = 0;
              for (i = 0; i < dim; i++)
                for (j = 0; j < dim; j++)
                  if (p & board[i][j][k][l].set)
                    n++;
              if (n == 4) {
                for (i = 0; i < dim; i++)
                  for (j = 0; j < dim; j++) {
                    var cell = board[i][j][k][l];
                    if (p & cell.set)
                      cell.set &= p;
                  }
              }
            }
}

// This is the main routine.  It creates a board, and then attempts to
// solve the puzzle it contains.
function solve() {
  board = Board();
  previous = Board();
  var u = unknowns();
  for (;;) {
    var v = u;
    if (show()) {
      put("The board is inconsistent.");
      board = null;
      return true;
    }
    if (u == dim2 * dim2)
      break;
    uniqall();
    u = unknowns();
    if (u < v) {
      put("By known value must be unique in row, column, and square.");
      continue;
    }
    uniqsqr();
    u = unknowns();
    if (u < v) {
      put("By only one place for value in square.");
      continue;
    }
    uniqrow();
    u = unknowns();
    if (u < v) {
      put("By only one place for value in row.");
      continue;
    }
    uniqcol();
    u = unknowns();
    if (u < v) {
      put("By only one place for value in column.");
      continue;
    }
    uniqrowline();
    u = unknowns();
    if (u < v) {
      put("By values in square preclude others in a row.");
      continue;
    }
    uniqcolline();
    u = unknowns();
    if (u < v) {
      put("By values in square preclude others in a column.");
      continue;
    }
    uniqcolsqr();
    u = unknowns();
    if (u < v) {
      put("By only one columm value in square.");
      continue;
    }
    uniqrowsqr();
    u = unknowns();
    if (u < v) {
      put("By only one row for value in square.");
      continue;
    }
    pairsqr();
    u = unknowns();
    if (u < v) {
      put("By pair has only two places in square.");
      continue;
    }
    pairrow();
    u = unknowns();
    if (u < v) {
      put("By pair has only two places in row.");
      continue;
    }
    paircol();
    u = unknowns();
    if (u < v) {
      put("By pair has only two places in column.");
      continue;
    }
    triplesqr();
    u = unknowns();
    if (u < v) {
      put("By triple has only three places in square.");
      continue;
    }
    triplerow();
    u = unknowns();
    if (u < v) {
      put("By triple has only three places in row.");
      continue;
    }
    triplecol();
    u = unknowns();
    if (u < v) {
      put("By triple has only three places in column.");
      continue;
    }
    quadsqr();
    u = unknowns();
    if (u < v) {
      put("By quad has only four places in square.");
      continue;
    }
    quadrow();
    u = unknowns();
    if (u < v) {
      put("By quad has only four places in row.");
      continue;
    }
    quadcol();
    u = unknowns();
    if (u < v) {
      put("By quad has only four places in column.");
      continue;
    }
    break;
  }
  put("Final board.");
  if (show()) {
    put("The board is inconsistent.");
    board = null;
    return true;
  }
  if (u > dim2 * dim2)
    put("The board is unfinished.");
  board = null;
  return true;
}

// Ensures a form value is blank or a non-zero digit.
function verify(e) {
  with (top.document.forms[0].elements[e]) {
    if (value != '1'
        && value != '2'
        && value != '3'
        && value != '4'
        && value != '5'
        && value != '6'
        && value != '7'
        && value != '8'
        && value != '9')
      value = '';
  }
  return true;
}

// Load a puzzle from the text area.

function load() {
  var p = top.document.forms[0].puzzle.value;
  // Ignore white space, hyphen, vertical bar, plus sign, and comments.
  p = p.replace(/[\s-|+]|\(.*\)/g, '');
  if (parse(p)) {
    alert('Bad puzzle syntax.');
    return true;
  }
  var i, j, k, l;
  var e = 0;
  with (top.document.forms[0])
    for (i = 0; i < dim; i++)
      for (k = 0; k < dim; k++)
        for (j = 0; j < dim; j++)
          for (l = 0; l < dim; l++) {
            var val = p.charAt(l + dim * (k + dim * (j + dim * i)));
            if (val == '.')
              val = '';
            elements[e++].value = val;
          }
  return true;
}

function parse(puzzle) {
  var n = puzzle.length;
  var i;
  for (i = 0; i < n; i++) {
    var value = puzzle.charAt(i);
    if (value != '.'
        && value != '1'
        && value != '2'
        && value != '3'
        && value != '4'
        && value != '5'
        && value != '6'
        && value != '7'
        && value != '8'
        && value != '9')
      return true;
  }
  return false;
}</script>
</head>

<body>
<div class="center"><form action="dummy" onSubmit="return false;">
  <table align="center" border="1">
    <tbody>
      <tr>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(0);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(1);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(2);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(3);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(4);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(5);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(6);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(7);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(8);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(9);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(10);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(11);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(12);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(13);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(14);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(15);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(16);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(17);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(18);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(19);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(20);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(21);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(22);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(23);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(24);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(25);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(26);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(27);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(28);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(29);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(30);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(31);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(32);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(33);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(34);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(35);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(36);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(37);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(38);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(39);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(40);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(41);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(42);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(43);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(44);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(45);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(46);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(47);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(48);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(49);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(50);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(51);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(52);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(53);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(54);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(55);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(56);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(57);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(58);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(59);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(60);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(61);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(62);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(63);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(64);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(65);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(66);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(67);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(68);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(69);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(70);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(71);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
        <td>
          <table border="1">
            <tbody>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(72);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(73);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(74);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(75);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(76);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(77);" type="text"></td>
              </tr>
              <tr>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(78);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(79);" type="text"></td>
                <td align="center"><input name="digit" size="1" value="" onChange="return verify(80);" type="text"></td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>

  <p>To solve the puzzle, press:
  <input type="button" value="Solve" onClick="solve(); return true;"></p>

  <p>To clear table, press:
  <input type="reset" value="Reset" name="Reset"></p>

  <p>To load a puzzle as text, enter it below and press:
    <input type="button" value="Load" onClick="load(); return true;">
    <br>
  </p>
  <p>
    <?php
//<p><textarea name="puzzle" rows="9" cols="9"></textarea>
global $db;
global $db_host;
global $db_user;
global $db_pwd;
global $database;
global $table;
global $puzzle;

$db_host = 'mysql.lisa-iclub.com';
$db_user = 'lisai001';
$db_pwd = 'lisa2007';
$database = 'lisai001';
$table = 'student1';
	
	
//$db_host = strip_tags($_POST['hostname']);
//$db_user = strip_tags($_POST['username']);
//$db_pwd = strip_tags($_POST['password']);
//$database = strip_tags($_POST['dbname']);
//$table = strip_tags($_POST['tablename']);
	
$db = mysql_connect($db_host,$db_user,$db_pwd,$database);

if(!$db)
{
	die("Unable: ");
}

mysql_select_db($database);

if (!mysql_select_db($database))
die("Can't select database");

// Sending query

$result = mysql_query("SELECT puzzle FROM `student1` WHERE id = 1");

if (!$result)
{
	die("Query to show fields from table failed");
}

//$fields_num = mysql_num_fields($result);

// Page formatting

echo "<p align='center'>Display Database Information</p>";
echo "<br>";
echo "<p>Database Name : {$database}</p>";
echo "<p>Table Name    : {$table}</p>";
echo "<br>";
//echo "<table border='1' class='td'><tr>";

// Printing table headers

//for($i=0; $i<$fields_num; $i++)
//{
	//$field = mysql_fetch_field($result);
	//echo $field->name;
	
	
	//echo "<textarea name="puzzle" rows="9" cols="9">{$field->name}</textarea>";
//}

//echo "</tr>\n";

// Printing table rows

while($row = mysql_fetch_row($result))
{
	//echo "<tr>";

	foreach($row as $cell){
	echo "<p>$cell</p>";
	$puzzle.=$cell;
	}
	
	echo "<p>$puzzle</p>";
	echo "<textarea name=\"puzzle\" rows=\"9\" cols=\"9\">$cell</textarea>";
	//$puzzle.=$row;
	
	//echo "</tr>\n";
}

//echo "<p>$result</p>";
mysql_free_result($result);

//echo "</table>";

//echo "<p>Puzzle    : {$puzzle}</p>";

?>
  </p>
  </p>
 </form>
</div>
<p>&nbsp;</p>
</body>
</html>
