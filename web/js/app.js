angular.module('CallService', ['ngResource','ngProgress']).factory('Call', ['$resource', function ($resource) {
    return $resource('api/call/:id');
}]);

function CallController($scope,Call,$http,$timeout,ngProgress){
    $scope.avaible=true;
    $scope.callStatus = "waiting";
    $scope.make = function(){
	
        var key = {};
        var value = {from:$scope.from,to:$scope.to}

        Call.save($scope.Call,function(sResponse){
		ngProgress.start();
        $scope.avaible = false;
            (function tick() {
                $http.post("api/check").success(function(data){
                    if(data.ends == ""){
                    $timeout(tick,3000);
                    } else {
			            ngProgress.complete();
                        $scope.avaible = true;
                    }
                })
            })();

        },function(errResponse){
	    ngProgress.complete();
            $scope.avaible = true;
            alert(errResponse.data.message);
        })


    }
}
