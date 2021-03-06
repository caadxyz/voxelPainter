<!DOCTYPE html>
<html>
<head>
    <title>Edytor map</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link type="text/css" rel="stylesheet" href="main.css"/>
</head>
<body>


<div id="infoPanel">
    <a href="http://threejs.org" target="_blank">three.js</a> - voxel painter
    <a download="document.json" href="javascript:save(scene,renderer);">save .png</a>
</div>

<div id="scenePanel" class="panel">
    <input type="range" name="height" id="height" value="1" min="0" max="10">
    <div  id="sceneControls" ><input type="text" name="sceneWidth" id="sceneWidth" value="500"><input type="text" name="sceneHeight" id="sceneHeight" value="500"><button id="sceneSubmit" onClick="updateGrid(document.getElementById('sceneWidth').value,document.getElementById('sceneHeight').value)">Change Grid</button><br/><button onClick="clearGrid()">Clear grid</button></div>
    <p id="voxel">Voxel height:</p><input type="range" name="singleHeight" id="singleHeight" value="1" min="0" max="10">
</div>

<div id="materialPanel" class="panel">
    <p>Define material:</p>
    <div id="textureMaterial">

        <?
        $files = glob("images/*.*");
        for ($i=1; $i<count($files); $i++)
        {
            $num = $files[$i];
            echo '<img src="'.$num.'" alt="random image" onclick="setTexture(this.src)">'."&nbsp;&nbsp;";
                }
        ?>

    </div>

    <select id="select">
        <option value="matt.png" selected="selected">matt</option>
        <option value="brick.png">brick</option>
        <option value="stone.png">stone</option>
        <option value="wall.png">wall</option>
        <option value="wood.png">wood</option>
    </select>
    <form action="upload_file.php" method="post"
          enctype="multipart/form-data">
        <label for="file">Filename:</label>
        <input type="file" name="file" id="file"><br>
        <input type="submit" name="submit" value="Submit">
    </form>

    <p>color(rgb)</p>
    <input type="number" class="material" name="matRed" id="matRed" value='0'>
    <input type="number" class="material" name="matGreen" id="matGreen" value='0'>
    <input type="number" class="material" name="matBlue" id="matBlue" value='0'>

    <p>Transparency</p><input type="range" name="matOpacity" id="matOpacity" value="20" min="0" max="20">
    <br>
    <button type="button" onclick="defineMaterial()">Define material</button>
</div>

<div id="currentPanel" class="panel">
    <p>Current Material: </p>

    <p>Texture: <span id="currentTexture">matt</span></p>

    <p>Color: <span id="currentColor">rgb(255,0,0)</span></p>

    <p>Opacity: <span id="currentOpacity">1</span></p>

</div>

<div id="actionPanel" class="panel">

    <ul>
        <br>
        <label class="share-label" for="share-toggle2">Set mode:</label>

        <div data-action="setMode" class="toggle">
            <li>
                <input type="radio" name="mode" id="create" onclick="setMode(this.value)" value="creatingMode"
                       checked="checked">
                <label class="toggle-radio" for="mode">Create new object </label>
            </li>
            <li>
                <input type="radio" name="mode" id="delete" onclick="setMode(this.value)" value="deletingMode">
                <label class="toggle-radio" for="mode">Delete objects</label>
            </li>
            <li>
                <input type="radio" name="mode" id="select" onclick="setMode(this.value)" value="selectionMode">
                <label class="toggle-radio" for="mode">Select objects</label>
            </li>
            <li>
                <input type="radio" name="mode" id="split" onclick="setMode(this.value)" value="splitingMode">
                <label class="toggle-radio" for="mode">Split object horizontally</label>
            </li>
        </div>
        </li>

    </ul>
    <ul>

        <label class="share-label" for="share-toggle2">Set action:</label>

        <div data-action="setAction" class="toggle">
            <li>
                <input type="radio" name="action" id="translate" onclick="setAction(this.value)" value="translate"
                       checked="checked">
                <label class="toggle-radio" for="share-toggle2"> Translate </label>
            </li>
            <li>
                <input type="radio" name="action" id="scale" onclick="setAction(this.value)" value="scale">
                <label class="toggle-radio" for="share-toggle1">Scale</label>
            </li>
            <li>
                <input type="radio" name="action" id="rotate" onclick="setAction(this.value)" value="rotate">
                <label class="toggle-radio" for="share-toggle1">Rotate</label>
            </li>
        </div>
        </li>
        <button type="button" onclick="createGroup()">Create Group from selection</button>
        <br/>
        <button type="button" onclick="splitGroup()">Split selected Group</button>



    </ul>
</div>


<script src="js/vendor/three.min.js"></script>
<script src="js/vendor/SceneExporter.js"></script>
<script src="js/vendor/FileSaver.js"></script>

<script src="js/loaders/ctm/lzma.js"></script>
<script src="js/loaders/ctm/ctm.js"></script>
<script src="js/loaders/ctm/CTMLoader.js"></script>

<script src="js/loaders/OBJLoader.js"></script>
<script src="js/loaders/VTKLoader.js"></script>
<script src="js/loaders/STLLoader.js"></script>
<script src="js/loaders/ColladaLoader.js"></script>
<script src="js/loaders/UTF8Loader.js"></script>
<script src="js/loaders/MTLLoader.js"></script>
<script src="js/cameraSettings.js"></script>

<script>


var mode="creatingMode";
var prevmode="creatingMode";

var action="translate";

function setAction(actionC)
{
    action=actionC;
}

var currentTexture='crate.jpg';

function setTexture(texture)
{
    currentTexture=texture;
    console.log("curetn" +currentTexture);
}

function setMode(modeC)
{
    mode=modeC;
}

function getMode(){
    return mode;
}


window.URL = window.URL || window.webkitURL;
window.BlobBuilder = window.BlobBuilder || window.WebKitBlobBuilder || window.MozBlobBuilder;

var container;
var camera, scene, renderer, mainContainer;
var projector;
var mouse2D, mouse3D, raycaster, theta = 45, target = new THREE.Vector3(0, 200, 0);
var isShiftDown = false, selectionMode = false, isMouseDown = false, splitingMode = false, isRDown = false, deletingMode = false, isTDown = false, isYDown = false;

var ROLLOVERED, R;

var allObjectsOnScene = [];

step = 50, sceneHeight = 1, singleHeight = 1;

var selectedIndex = 0;
var groupContainer = [];

var map = THREE.ImageUtils.loadTexture('crate.jpg');
//zaznaczanie
var selObj = new Object();
var isSelected = 0;


var definedMaterial = new THREE.MeshLambertMaterial({map:map,
    color: 0xee0000, transparent:true, opacity:1
});

function defineMaterial(){
    var map = THREE.ImageUtils.loadTexture(currentTexture);
    var opacity=document.getElementById('matOpacity').value/20.;
    console.log("color1"+definedMaterial.color.value);
    var matRed= parseInt(document.getElementById('matRed').value);
    var matGreen= parseInt(document.getElementById('matGreen').value);
    var matBlue= parseInt(document.getElementById('matBlue').value);
      console.log("rgb:"+matRed+matGreen+matBlue);
    var color=new THREE.Color('rgb('+matRed+','+matGreen+','+matBlue+')');
    var isTransparent=true;
    definedMaterial = new THREE.MeshLambertMaterial({  map:map,
        color: color , transparent:isTransparent, opacity:opacity
    });
    updateCurrentData(matRed,matGreen,matBlue, opacity);
}

function updateCurrentData(r, g, b, opacity){
    document.getElementById("currentTexture").innerHTML=currentTexture;
    document.getElementById("currentColor").innerHTML="rgb("+r+","+g+","+b+")";

    document.getElementById("currentOpacity").innerHTML=opacity;

}

var glassMaterial = new THREE.MeshLambertMaterial({
    color: 0x6666EE, transparent: true, opacity: 0.5
});


var lineMaterial = new THREE.LineBasicMaterial({
    color: 0x000000,
    opacity: 0.2
});
var split = false;
var seg = 0;

init();
animate();

function init() {

    container = document.createElement('div');
    document.body.appendChild(container);

  /*  var sceneControls = document.createElement('div');
    sceneControls.innerHTML = '*/
    var form = document.createElement('div');
    form.innerHTML = '<div id="fileForm" class="panel"><p>Specify a file, or a set of files:<br><form id="fileUploadForm" enctype="multipart/form-data" action="/martusia/level-layout-editor/upload.php" method="POST"><input type="file" id="input" name="input" size="40"></form></p></div>'

    container.appendChild(form);/*
    container.appendChild(sceneControls);*/

    heightController = document.getElementById('height');

    camera = new THREE.PerspectiveCamera(40, window.innerWidth / window.innerHeight, 1, 10000);
    camera.position.y = 800;

    scene = new THREE.Scene();
    mainContainer = new THREE.Object3D();
    scene.add(mainContainer);

    setLightRenderingAndPlane();
}

function onDocumentMouseMove(event) {
    event.preventDefault();

    mouse2D.x = ( event.clientX / window.innerWidth ) * 2 - 1;
    mouse2D.y = -( event.clientY / window.innerHeight ) * 2 + 1;

    var intersects = raycaster.intersectObjects(allObjectsOnScene, true);

    if (intersects.length > 0) {

        if (ROLLOVERED) {
            ROLLOVERED.face.color.setHex(0x408080);
            R.colorsNeedUpdate = true;
        }

        ROLLOVERED = intersects[ 0 ];
        console.log(ROLLOVERED.face.color.getHex());
        ROLLOVERED.face.color.setHex(0xff8800);
        R = intersects[ 0 ].object.geometry;
        R.colorsNeedUpdate = true;
    }

    createSegment();
}

function onDocumentMouseDown(event) {
    event.preventDefault();
    isMouseDown = true;
    createSegment();
}

function onDocumentMouseUp(event) {

    event.preventDefault();
    isMouseDown = false;
}

function onDocumentKeyDown(event) {

    switch (event.keyCode) {

        case 16:
            isShiftDown = true;
            break;
        case 17:
            prevmode=getMode();
            mode="selectionMode";
            break;
        case 'Q'.charCodeAt(0):
            prevmode=getMode();
            mode="splitingMode";
            break;
        case 'R'.charCodeAt(0):
            isRDown = true;
            break;
        case 46:
            prevmode=getMode();
            mode="deletingMode";
            break;
        case 'M'.charCodeAt(0):
            createGroup();
            break;
        case 'N'.charCodeAt(0):
            splitGroup();
            break;
        case 'U'.charCodeAt(0):
            setMaterial1(definedMaterial);
            break;

        //ruch
        case 'I'.charCodeAt(0):
            modifyCubes(0, 1, 0);
            break;
        case 'K'.charCodeAt(0):
            modifyCubes(0, -1, 0);
            break;
        case 'D'.charCodeAt(0):
            modifyCubes(1, 0, 0);
            break;
        case 'A'.charCodeAt(0):
            modifyCubes(-1, 0, 0);
            break;
        case 'W'.charCodeAt(0):
            modifyCubes(0, 0, -1);
            break;
        case 'S'.charCodeAt(0):
            modifyCubes(0, 0, 1);
            break;
        case 'T'.charCodeAt(0):
            isTDown = true;
            break;
        case 'Y'.charCodeAt(0):

            isYDown = true;
            break;
    }
}

function onDocumentKeyUp(event) {

    switch (event.keyCode) {

        case 16:
            isShiftDown = false;
            break;
        case 17:
            mode=prevmode;
            break;
        case 81:
            mode=prevmode;
            break;
        case 82:
            isRDown = false;
            break;
        case 46:
            mode=prevmode;
            break;
        case 'T'.charCodeAt(0):
            isTDown = false;
            break;
        case 'Y'.charCodeAt(0):
            isYDown = false;
            break;
    }
}

function selectObjOrGroup(intersects) {
    if (intersects[0].object != plane) {
        if (intersects[0].object.parent == mainContainer)
            Select(intersects[0].object);
        else {
            selectedIndex = getIndex(intersects[0].object);
            for (var i = 0; i < intersects[0].object.parent.children.length; i++)
                Select(intersects[0].object.parent.children[i]);

        }
    }
}
function setTranspObjOrGroup(intersects) {
    if (intersects[0].object != plane) {
        if (intersects[0].object.parent == mainContainer)
            setTransparency(intersects[0].object, 0.7);
        else {

            for (var i = 0; i < intersects[0].object.parent.children.length; i++)
                setTransparency(intersects[0].object.parent.children[i], 0.7);

        }
    }
}
function setMaterialObjOrGroup(intersects) {
    if (intersects[0].object != plane) {
        if (intersects[0].object.parent == mainContainer)
            setMaterial(intersects[0].object, definedMaterial);
        else {

            for (var i = 0; i < intersects[0].object.parent.children.length; i++)
                setMaterial(intersects[0].object.parent.children[i], definedMaterial);

        }
    }
}
function deleteObjOrGroup(intersects) {
    if (intersects[0].object != plane) {
        if (intersects[0].object.parent == mainContainer)
            intersects[0].object.parent.remove(intersects[0].object);
        else {
            for (var i = intersects[0].object.parent.children.length - 1; i >= 0; --i)
                intersects[0].object.parent.parent.remove(intersects[0].object.parent);

        }
    }
}
function createSegment() {
    var intersects = raycaster.intersectObjects(scene.children, true);
    var INTERSECTED;

    if (intersects.length > 0) {

        if (isMouseDown) {
            if (mode=="selectionMode") {
                selectObjOrGroup(intersects);
            }
            else if (isTDown) {
                setTranspObjOrGroup(intersects);
            }

            else if (isYDown) {
                setMaterialObjOrGroup(intersects);
            }
            else if (mode=="splitingMode") {
                if (intersects[0].object != plane) {
                    splitSegment(intersects[0].object);
                }
            }
            else if (mode=="deletingMode") {
                deleteObjOrGroup(intersects);
            }

            else {
                var position = new THREE.Vector3().add(intersects[0].point, intersects[0].object.matrixRotationWorld.multiplyVector3(intersects[0].face.normal.clone()));
                if (intersects[0].faceIndex != 2) {
                    addSegment(position);
                }
            }
        }
    }
}

function getIndex(obj) {
    for (i = 0; i < groupContainer.length; i++) {
        if (obj.parent == groupContainer[i]) {
            console.log("getindex:" + selectedIndex);
            selectedIndex = i;
            break;
        }
    }
    return selectedIndex;
}

function Select(obj) {

    if (obj != plane) {
        INTERSECTED = obj;

        if (INTERSECTED.material.emissive.getHex() == 0x000000) {
            INTERSECTED.material.emissive.setHex(0xff0000);
            selObj[obj.name] = obj.name;
        }
        else {
            INTERSECTED.material.emissive.setHex(0x000000);
            console.log("wunselekcie" + selectedIndex);
            delete selObj[obj.name];
        }
    }
}


function addSegment(position) {
    singleHeight = document.getElementById('singleHeight').value;
    var geometry = new THREE.CubeGeometry(50, 50 * singleHeight, 50);
    seg++;
    console.log("ilosc seg:" + seg);
    for (var i = 0; i < geometry.faces.length; i++) {

        geometry.faces[i].color.setHex(0x408080);
    }


    var material = new THREE.MeshLambertMaterial();
    material=definedMaterial.clone();
    /*var material = new THREE.MeshLambertMaterial({
     vertexColors: THREE.FaceColors
     });*/

    var voxel = new THREE.Mesh(geometry, material);
    voxel.name = "Segment_" + voxel.id;
    voxel.position.x = Math.floor(position.x / 50) * 50 + 25;
    voxel.position.y = Math.floor(position.y / 50) * 50 + 25;
    voxel.position.z = Math.floor(position.z / 50) * 50 + 25;
    voxel.matrixAutoUpdate = false;

    console.log(parseInt(singleHeight) * 50 / 2);
    voxel.position.y = parseInt(singleHeight) * 50 / 2;
    voxel.updateMatrix();
    mainContainer.add(voxel);
    var vox = new THREE.Object3D();
    vox = voxel;
    allObjectsOnScene.push(voxel);
}

function addSegmentSplited(position) {

    var geometry = new THREE.CubeGeometry(50, 50, 50);

    seg++;
    console.log("ilosc seg:" + seg);
    for (var i = 0; i < geometry.faces.length; i++) {

        geometry.faces[i].color.setHex(0x408080);
    }

    var material = new THREE.MeshLambertMaterial();
    material=definedMaterial.clone();


    var voxel = new THREE.Mesh(geometry, material);
    voxel.name = "Segment_" + voxel.id;
    voxel.position.x = position.x;
    voxel.position.y = position.y;
    voxel.position.z = position.z;
    voxel.matrixAutoUpdate = false;
    voxel.updateMatrix();
    mainContainer.add(voxel);
    var vox = new THREE.Object3D();
    vox = voxel;
    allObjectsOnScene.push(voxel);
}


function createGroup() {
    var children = mainContainer.children.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    var tempContainer = new THREE.Object3D();
    children.forEach(function (e) {
        tempContainer.add(e);
    });
    groupContainer.push(tempContainer);
    console.log("create:" + groupContainer.length);
    scene.add(groupContainer[groupContainer.length - 1]);
}

function splitGroup() {
    console.log("selindex:" + selectedIndex);
    var children = groupContainer[selectedIndex].children;
    var length = children.length;
    for (var i = length - 1; i >= 0; i--) {
        mainContainer.add(children[i]);
        delete selObj[children.name];
    }
    delete groupContainer[selectedIndex];
    for (i = selectedIndex; i < groupContainer.length; i++) {
        groupContainer[i] = groupContainer[i + 1];
        groupContainer.length = groupContainer.length - 1;
    }
    console.log("split:" + groupContainer.length);
}


function splitSegment(voxel) {
    var obj = voxel;
    var parts = obj.geometry.height / 50;
    console.log("parts " + parts);
    console.log(obj);
    split = true;
    if (parts > 1) {
        voxel.parent.remove(voxel);

        for (var i = 0; i < parts; i += 1) {
            var h = new THREE.Vector3(obj.position.x, 25 + i * 50, obj.position.z);
            //h.addSelf(obj.position);
            //console.log("obj.pos: ");
            //console.log(obj.position);
            //console.log("h: ")
            //console.log(obj.position);
            addSegmentSplited(h);
        }
    }
    split = false;
}

function joinSegments() {
    if (isRDown) {

        var children = mainContainer.children.filter(function (e) {
            return (typeof selObj[e.name] != 'undefined');
        });

        var obj = children[0];
        var group = new THREE.Object3D();
        children.forEach(function (e) {
            group.add(e);
            mainContainer.remove(e);
        });
        mainContainer.add(group);
    }
}

function modifyCubes(dx,dy,dz){
    if (action=="scale")
        scaleCubes(dx, dy, dz);
    else if (action=="translate")
        translateCubes(dx,dy,dz);
    else if (action=="rotate")
            rotateCubes(dx, dy, dz);
}

function scaleCubes(dx, dy, dz) {
    console.log("scaleCubes", dx, dy, dz)
    //var children = objects;
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    var voxels = [];

    for (var i = 0; i < children.length; i++) {
        var child = children[i];

        if (child instanceof THREE.Mesh === false)            continue;
        if (child.geometry instanceof THREE.CubeGeometry === false)    continue;

        if ((child.scale.x<1 && dx<0) || (child.scale.y<1 && dy<0) || (child.scale.z<1 &&dz<0)) continue;
        child.scale.x += dx/2;
        child.scale.y += dy/2;
        child.scale.z += dz/2;

        child.updateMatrix();
    }
}

function rotateCubes(dx, dy, dz) {
    console.log("scaleCubes", dx, dy, dz)
    //var children = objects;
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    var voxels = [];

    for (var i = 0; i < children.length; i++) {
        var child = children[i];

        if (child instanceof THREE.Mesh === false)            continue;
        if (child.geometry instanceof THREE.CubeGeometry === false)    continue;


        child.rotation.x += dx * Math.PI / 180;
        child.rotation.y += dy * Math.PI / 180;
        child.rotation.z += dz * Math.PI / 180;

        child.updateMatrix();
    }
}


function deleteSelected() {
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    for (var i = children.length - 1; i >= 0; --i) {
        var child = children[i];
        if (child instanceof THREE.Mesh === false)            continue;
        if (child.geometry instanceof THREE.CubeGeometry === false)    continue;


        child.parent.remove(child);
        child.updateMatrix();


    }
}


function translateCubes(dx, dy, dz) {
    console.log("translateCubes", dx, dy, dz)
    //var children = objects;
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });

    for (var i = 0; i < children.length; i++) {
        var child = children[i];

        if (child instanceof THREE.Mesh === false)            continue;
        if (child.geometry instanceof THREE.CubeGeometry === false)    continue;

        child.position.x += dx * 25;
        child.position.y += dy * 25;
        child.position.z += dz * 25;

        child.updateMatrix();
    }
}

function setTransparency(obj, opacity) {

    if (obj != plane) {
        INTERSECTED = obj;
        INTERSECTED.material.transparent = true;
        INTERSECTED.material.opacity = opacity;
    }
}

function setMaterial(obj, material) {
    if (obj != plane) {
        INTERSECTED = obj;
        var currentSelect = INTERSECTED.material.emissive.getHex();
        var materialNew = material.clone();
        materialNew.emissive.setHex(currentSelect);
        INTERSECTED.material = materialNew;
    }
}

function setMaterial1( material) {
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    children.forEach(function (e) {

        var currentSelect = e.material.emissive.getHex();
        var materialNew = material.clone();
        materialNew.emissive.setHex(currentSelect);
        e.material = materialNew;
    });
}

function onHeightChange(event) {

    sceneHeight = event.srcElement.value;

    //var children = scene.children.filter(function(e){ return (e.name.substring(0, 7) == "Segment"); });
    var children = allObjectsOnScene.filter(function (e) {
        return (typeof selObj[e.name] != 'undefined');
    });
    console.log("liczba znalezionych dzieci: " + Object.getOwnPropertyNames(children).length);
    children.forEach(function (e) {
        console.log(e.name);
        var scale = e.scale.y;
        e.scale.y = parseInt(sceneHeight);
        e.position.y = e.geometry.height * e.scale.y / 2;
        e.updateMatrix();
    });
}

document.getElementById('input').addEventListener('change', handleFileSelect, false);
var loader = new THREE.SceneLoader();

loader.addGeometryHandler("ctm", THREE.CTMLoader);
loader.addGeometryHandler("vtk", THREE.VTKLoader);
loader.addGeometryHandler("stl", THREE.STLLoader);

loader.addHierarchyHandler("obj", THREE.OBJLoader);
loader.addHierarchyHandler("dae", THREE.ColladaLoader);
loader.addHierarchyHandler("utf8", THREE.UTF8Loader);

loader.load('test.json', callbackFinished);

/*<br><strong>click</strong>: add
voxel,
<strong>control + click</strong>: select voxel, <strong>shift</strong>: rotate, <br><strong>click+delete</strong>:
remove,<strong>asdw</strong>: move, M- creates a group<br>*/
</script>
</body>
</html>
