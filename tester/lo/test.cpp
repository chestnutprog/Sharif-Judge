#include<iostream>
using namespace std;
int main(){
	int n,a=0;
	cin>>n;
	for(int i=1;i<=n;i++){
		int sum;
		cin>>sum;
		a+=sum;
	}
	cout<<a;
}