# NEO UI Kit
## CLI Commands
`gulp serve` - run develop mode.

# Framework
## Architecture
All framework files is contained in `neo/` folder. <br>
Here you can find built framework in `dist/` folder, and source file in `src/` folder. <br>

# Framework in-use
## Margins, paddings...
`.ml-5` is equal to `margin-left: 5px;` <br>
`.pb-49` is equal to `padding-bottom: 49px;` <br>
You can set inline margins and paddings from **1** to **50**! Step - 1px.

## Adaptive? Of course!
`.mw-150` is equal to `min-width: 150px;` <br>
`.mh-1000` is equal to `min-height: 1000px;` <br>
You can set max-width and height up to **10** (equals **1000px**). Step - 50px. <br><br>

Other examples:<br>
`.mww-250` equals `max-width: 250px;` <br>
`.w-auto` equals `width: auto;` <br>
`.h-100-percents` equals `height: 100%;` <br>

## Maybe grid like Bootstrap?
Isn't! You can include it massive framework, but in Neo we use default CSS Grid System or Flexbox.

### Flex
`.d-flex` is equal to `display: flex;` <br>
`.vertical-center-flex` equals `justify-content: center;` <br>
`.horizontal-center-flex` equals `align-items: center;` <br>

### Grid
`.d-grid` is equals `display: grid;` <br>

## And other good stuff!
`.card` - make element white and aa shadow to it. <br>
`.bubble` - make element zoomed-in-out on hover. <br>